<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Assembler (Two passes)</title>
    </head>
    <body>
        <?php
        $file = set_locations(read_prog("hi.txt"));
        $object_prog = array(array());
        $file = Forward_Reference($file);
        print_all($file);

        function ASCII($string) {
            $code = "";
            for ($i = 0; $i < strlen($string); $i++) {
                $code.= base_convert(ord($string[$i]), 10, 16);
            }
            return $code;
        }

        function print_all($file) {
            echo'<table>';
            echo '<th>location</th><th>label</th><th>operands</th><th>mnemonic</th><th>objectCode</th>';
            foreach ($file as $row) {
                echo '<tr>';
                if (array_key_exists('loc', $row))
                    echo '<td><center>' . $row['loc'] . '</center></td>';
                else
                    echo'<td></td>';
                if (array_key_exists('label', $row))
                    echo '<td><center>' . $row['label'] . '</center></td>';
                else
                    echo'<td></td>';
                if (array_key_exists('operands', $row))
                    echo '<td><center>' . $row['operands'] . '</center></td>';
                else
                    echo'<td></td>';
                if (array_key_exists('mnemonic', $row))
                    echo '<td><center>' . $row['mnemonic'] . '</center></td>';
                else
                    echo'<td></td>';
                if (array_key_exists('objectCode', $row))
                    echo '<td><center>' . $row['objectCode'] . '</center></td>';
                else
                    echo'<td></td>';
                echo '</tr>';
            }
            echo'</table>';
        }

        function read_prog($file_name) {
            $file = file("programs/" . $file_name);
            foreach ($file as $key => $value) {
                if (substr_count($value, ";") > 0) { //Removes comments
                    $file[$key] = substr($file[$key], 0, stripos($file[$key], ";"));
                    $value = $file[$key];
                }
                $file[$key] = array();
                if (str_word_count(trim($value)) == 0) {//elemenate empty lines
                    unset($file[$key]);
                } else {
                    $temp = preg_split("/[\s]+/", trim($value));
                    if (count($temp) == 3) {
                        $file[$key]["label"] = $temp[0];
                        $file[$key]["operands"] = $temp[1];
                        $file[$key]["mnemonic"] = $temp[2];
                    } else if (count($temp) == 2) {
                        $file[$key]["operands"] = $temp[0];
                        $file[$key]["mnemonic"] = $temp[1];
                    } else {
                        die("Error at Line " . ($key + 1) . " <br>number of words = " . str_word_count(trim($value)));
                    }
                }
            }
            return $file;
        }

        function check_END($file) {

            if (strcasecmp($file[array_keys($file)[count(array_keys($file)) - 1]]["operands"], "END") == 0) {
                if (strcasecmp($file[array_keys($file)[0]]["operands"], "START") == 0) {//if programs contains start statement
                    if ($file[array_keys($file)[0]]["mnemonic"] != $file[array_keys($file)[count(array_keys($file)) - 1]]["mnemonic"]) {
                        die("Error<br>The END address not equal to the START address");
                    }
                } else {
                    if (0 != $file[array_keys($file)[count(array_keys($file)) - 1]]["mnemonic"]) {
                        die("Error<br>The END address not equal to 0000");
                    }
                }
            } else
                die("Error<br>There is no END statement");
        }

        function unique_labels($file) {
            if (count(array_unique(array_column($file, 'label'))) < count(array_column($file, 'label'))) {
                foreach ($file as $i => $v1) {
                    foreach ($file as $j => $v2) {
                        if ($j < $i) {
                            if (array_key_exists('label', $file[$i]) && array_key_exists('label', $file[$j])) {//check if line contains label
                                if ($file[$i]['label'] == $file[$j]['label']) {
                                    die("Error<br>Duplicated labels at lines " . ($i + 1) . " and" . ($j + 1) . "<br>");
                                }
                            }
                        }
                    }
                }
            }
        }

        function read_opcode() {
            $opcode = array();
            $temp = file("SIC instructions.txt");
            foreach ($temp as $key => $value) {
                $temp[$key] = preg_split("/[\s]+/", trim($value));
                $opcode[$temp[$key][0]] = $temp[$key][1];
            }
            return $opcode;
        }

        function set_locations($file) {
            $temp = $file[array_keys($file)[0]]["mnemonic"];
            if (strcasecmp($file[array_keys($file)[0]]["operands"], "START") == 0) {
                if (ctype_xdigit($temp) && strlen($temp) > 0 && strlen($temp) <= 4)
                    $file[array_keys($file)[1]]["loc"] = $file[array_keys($file)[0]]["mnemonic"];
                else
                    die("Error at Line 1<br>Start Location must be number between 0000 and FFFF");
            } else {
                $file[array_keys($file)[1]]["loc"] = 0;
            }
            check_END($file);
            unique_labels($file);
            $opcode = read_opcode();
            $GLOBALS['opcode'] = $opcode;
            $array_keys = array_keys($file);
            //print_r($array_keys);
            foreach ($array_keys as $key => $value) {
                if (strcasecmp($file[$value]["operands"], "START") != 0 && strcasecmp($file[$value]["operands"], "END") != 0) {
                    if (array_key_exists(strtoupper($file[$value]["operands"]), $opcode)) {
                        if (strcasecmp(substr($file[$value]["mnemonic"], -2), ",x") != 0) {
                            if (!in_array($file[$value]["mnemonic"], array_column($file, 'label')))
                                die('Erorr at line ' . ($value + 1) . '<br>undefined symbol');
                            else {
                                $file[$array_keys[$key + 1]]['loc'] = base_convert(base_convert($file[$value]["loc"], 16, 10) + 3, 10, 16);
                                $file[$value]["objectCode"] = $opcode[$file[$value]["operands"]] . "0000";
                            }
                        } else if (!in_array(substr($file[$value]["mnemonic"], 0, -2), array_column($file, 'label'))) {
                            die('Erorr at line ' . ($value + 1) . '<br>undefined symbol');
                        } else {
                            $file[$array_keys[$key + 1]]['loc'] = base_convert(base_convert($file[$value]["loc"], 16, 10) + 3, 10, 16);
                            $file[$value]["objectCode"] = $opcode[$file[$value]["operands"]] . "8000";
                        }
                    } else if (strcasecmp($file[$value]["operands"], "WORD") == 0) {
                        if (!array_key_exists('label', $file[$value]))
                            die('Erorr<br>there is no label at line ' . ($value + 1));
                        $file[$array_keys[$key + 1]]['loc'] = base_convert(base_convert($file[$value]["loc"], 16, 10) + 3, 10, 16);
                        if (is_numeric($file[$value]["mnemonic"])) {
                            $file[$value]["objectCode"] = base_convert($file[$value]["mnemonic"], 10, 16);
                        } else
                        if (strcasecmp(substr($file[$value]["mnemonic"], 0, 2), '0x') == 0) {
                            $file[$value]["objectCode"] = substr($file[$value]["mnemonic"], 2);
                        } else
                            die("Error<br>Invalid mnemonic at line " . ($value + 1));
                    } elseif (strcasecmp($file[$value]["operands"], "RESW") == 0) {
                        if (!array_key_exists('label', $file[$value]))
                            die('Erorr<br>there is no label at line ' . ($value + 1));
                        $file[$array_keys[$key + 1]]['loc'] = base_convert(base_convert($file[$value]["loc"], 16, 10) + (3 * base_convert($file[$value]["mnemonic"], 16, 10)), 10, 16);
                    } elseif (strcasecmp($file[$value]["operands"], "RESB") == 0) {
                        if (!array_key_exists('label', $file[$value]))
                            die('Erorr<br>there is no label at line ' . ($value + 1));
                        $file[$array_keys[$key + 1]]['loc'] = base_convert(base_convert($file[$value]["loc"], 16, 10) + (base_convert($file[$value]["mnemonic"], 16, 10)), 10, 16);
                    } elseif (strcasecmp($file[$value]["operands"], "BYTE") == 0) {
                        if (!array_key_exists('label', $file[$value]))
                            die('Erorr<br>there is no label at line ' . ($value + 1));
                        if (strtoupper($file[$value]["mnemonic"][0]) == 'C') {
                            if (substr($file[$value]["mnemonic"], -1) == "'" && $file[$value]["mnemonic"][1] == "'") {
                                $const_length = strlen($file[$value]["mnemonic"]) - 3; //minus 3 chars (c,',')
                                $file[$array_keys[$key + 1]]['loc'] = base_convert(base_convert($file[$value]["loc"], 16, 10) + $const_length, 10, 16);
                                $file[$value]["objectCode"] = ASCII(substr($file[$value]["mnemonic"], 2, $const_length));
                            } else
                                die("Error<br>Invalid mnemonic at line " . ($value + 1) . "<br>must contains single quotations (' ')");
                        }else if (strtoupper($file[$value]["mnemonic"][0]) == 'X') {
                            $file[$array_keys[$key + 1]]['loc'] = base_convert(base_convert($file[$value]["loc"], 16, 10) + 1, 10, 16);
                            $const_length = strlen($file[$value]["mnemonic"]) - 3;
                            if (ctype_xdigit(substr($file[$value]["mnemonic"], 2, $const_length)))
                                $file[$value]["objectCode"] = substr($file[$value]["mnemonic"], 2, $const_length);
                            else
                                die("Erorr at line" . ($value + 1) . "<br>number of device must be in hexadecimal ");
                        } else
                            die("Error<br>Invalid mnemonic at line " . ($value + 1));
                    } else
                        die("Error at line " . ($value + 1));
                }
            }
            return $file;
        }

        function Forward_Reference($file) {
            $array_keys = array_keys($file);
            $opcode = $GLOBALS['opcode'];
            foreach ($array_keys as $key => $value) {
                if (array_key_exists(strtoupper($file[$value]["operands"]), $opcode)) {
                    if (strcasecmp(substr($file[$value]["mnemonic"], -2), ",x") == 0) {
                        $temp1 = base_convert($file[$value]["objectCode"], 16, 10);
                        $temp2 = base_convert(array_search(substr($file[$value]["mnemonic"], 0, -2), array_column($file, "label", "loc")), 16, 10);
                        $file[$value]["objectCode"] = base_convert($temp1 + $temp2, 10, 16);
                    } else {
                        $temp1 = base_convert($file[$value]["objectCode"], 16, 10);
                        $temp2 = base_convert(array_search($file[$value]["mnemonic"], array_column($file, "label", "loc")), 16, 10);
                        $file[$value]["objectCode"] = base_convert($temp1 + $temp2, 10, 16);
                    }
                }
                if (key_exists("objectCode", $file[$value]) && strcasecmp(substr($file[$value]["mnemonic"], 0, 2), "x'")&&strcasecmp(substr($file[$value]["mnemonic"], 0, 2), "c'")) {
                    if (strlen($file[$value]["objectCode"]) < 6) {
                        $file[$value]["objectCode"] = str_repeat('0', 6 - strlen($file[$value]["objectCode"])) . $file[$value]["objectCode"];
                    }
                }
                if (key_exists("loc", $file[$value]))
                if (strlen($file[$value]["loc"]) < 4) {
                    $file[$value]["loc"] = str_repeat('0', 4 - strlen($file[$value]["loc"])) . $file[$value]["loc"];
                }
            }
            return $file;
        }
        ?>

    </body>
</html>
