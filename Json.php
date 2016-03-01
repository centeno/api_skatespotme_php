<?php
/**
 * Converte objetos e arrays para a notacao json com suporte a caracteres Latin
 * e Latin-1 Supplement (\u0000 - \u001f, \u0022, \u0027, \u007f - \u00ff
 * http://en.wikipedia.org/wiki/List_of_Unicode_characters)
 * 
 * @author Wagner Brandao Soares (http://www.wagnersoares.com.br)
 */
class Json {
    /**
     * Converte objetos e arrays para a notacao json
     * 
     * @param mixed $value
     * @return string
     */
    public function encode($value) {
        return $this->_encodeValue($value);
    }
    /**
     * Converte a notacao json para objetos e arrays
     * 
     * @param string $string
     * @return mixed 
     */
    public function decode($string) {
        $result = json_decode($string);
        
        return $this->_decodeValue($result);
    }
    /**
     * Converte os valores conforme o seu tipo
     * 
     * @param mixed $value
     * @return string 
     */
    private function _encodeValue($value) {
        if(is_object($value)) {
            return $this->_objectToJson($value);
        } else if(is_array($value)) {
            return $this->_arrayToJson($value);
        }

        return $this->_encodeData($value);
    }
    /**
     * Converte um objeto para a notacao json
     * 
     * @param object $object
     * @return string 
     */
    private function _objectToJson($object) {
        $json = array();
        foreach ($object as $name => $value) {
            $json[] = $this->_stringToUnicode($name) . ':' . $this->_encodeValue($value);
        }
        
        return '{' . implode(',', $json). '}';
    }
    /**
     * Converte um array para a notacao json
     * 
     * @param array $array
     * @return string 
     */
    private function _arrayToJson($array) {
        
        $json = array();
        $result = '';
        
        if(!empty($array) && (array_keys($array) !== range(0, count($array) - 1))) {
            $result = '{';
            foreach ($array as $key => $value) {
                $key = (string) $key;
                $json[] = $this->_stringToUnicode($key) . ':' . $this->_encodeValue($value);
            }
            $result .= implode(',', $json);
            $result .= '}';
        } else {
            $result = '[';
            $length = count($array);
            for($index = 0; $index < $length; $index++) {
                $json[] = $this->_encodeValue($array[$index]);
            }
            $result .= implode(',', $json);
            $result .= ']';
        }
        
        return $result;
    }
    /**
     * Converte valores para a construcao da notacao json
     * 
     * @param mixed $value
     * @return string 
     */
    private function _encodeData($value)
    {
        $result = 'null';

        if(is_int($value) || is_float($value)) {
            $result = (string) $value;
            $result = str_replace(",", ".", $result);
        } else if(is_string($value)) {
            $result = $this->_stringToUnicode($value);
        } else if(is_bool($value)) {
            $result = $value ? 'true' : 'false';
        }

        return $result;
    }
    /**
     * Converte caracteres Latin e Latin-1 Supplement para unicode
     * 
     * @param string $value
     * @return string 
     */
    private function _stringToUnicode($value) {
        if(mb_detect_encoding($value, 'UTF-8', true) !== false) {
            $value = utf8_decode($value);
        }
        
        $strlen = strlen($value);
        $string = "";
        
        for($index = 0; $index < $strlen; $index++) {
            $ascii = ord($value[$index]);
            
            if(($ascii >= 32) && ($ascii <= 126) && ($ascii != 34) && ($ascii != 39)){
                $string .= $value[$index];
            } else {
                $string .= '\u'.str_pad(dechex($ascii), 4, '0', STR_PAD_LEFT);
            }
        }

        return '"' . $string . '"';
    }
    /**
     * Percorre arrays e objetos e valores para normalizacao de caracteres Latin e
     * Latin-1 Supplement convertidos em utf8
     * 
     * @param mixed $value
     * @return mixed 
     */
    private function _decodeValue($value) {
        if(is_object($value)) {
            return $this->_jsonToObject($value);
        } else if(is_array($value)) {
            return $this->_jsonToArray($value);
        } else if(is_string($value)) {
            return $this->_utf8ToIso($value);
        }

        return $value;
    }
    /**
     * Percorre objetos para normalizacao de caracteres Latin e
     * Latin-1 Supplement convertidos em utf8
     * 
     * @param object $object
     * @return mixed 
     */
    private function _jsonToObject($object) {
        $array = (array)$object;
        
        if(preg_match('/(,\d+[,]?)|([,?]\d+,)|(^\d+$)/', implode(',',array_keys($array)))) {
            return $this->_jsonToArray($array);
        } else {
            $newObj = new stdClass();
            
            foreach ($array as $name => $value) {
                $name = $this->_utf8ToIso($name);
                $newObj->{$name} = $this->_decodeValue($value);
            }
            
            return $newObj;
        }
    }
    /**
     * Percorre arrays para normalizacao de caracteres Latin e
     * Latin-1 Supplement convertidos em utf8
     * 
     * @param array $array
     * @return array 
     */
    private function _jsonToArray($array) {
        $newArray = array();
        
        foreach ($array as $key => $value) {
            if(preg_match('/^\d+$/',$key)) {
                $key = (int)$key;
            } else {
                $key = $this->_utf8ToIso($key);
            }
            
            $newArray[$key] = $this->_decodeValue($value);
        }
        
        return $newArray;
    }
    /**
     * Converte caracteres UTF8 para ISO
     * 
     * @param string $value
     * @return string 
     */
    private function _utf8ToIso($value) {
        return mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
    }
}