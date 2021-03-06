<?php

class Lexer {
	
	//tokens of the lexical grammar
	public $tokens = array();
	
	//array of reserved words of the lexical grammar
	public $reserved = array();
	
	//var array
	private $code;
	private $result = array();
	
	//analyze the code
	public function analyze($code) {
		
		// split code into separate lines
		$this->code = explode(PHP_EOL, $code);
		
		// analyze each line of code
		foreach ($this->code as $line_number => $line_code) {
			$line_length = strlen($line_code);
			$offset = 0; // offset from the beginning of line
			$prev_delimiter = false;
			while ($offset < $line_length) {
				// select substring from offset to the end of line
				$str = substr($line_code, $offset);
				$lexeme = $this->get_lexeme($str);
				if ($lexeme['type'] !== '') {
					// if lexeme found
					// if current lexeme is delimiter
					if ($lexeme['delimiter'] === true) {
						$prev_delimiter = true;
						// put info about visible token-delimiter to the result
						if ($lexeme['visible'] !== false) {
							$this->result[] = array(
								'row' => $line_number,
								'col' => $offset,
								't_value' => $lexeme['value'],
								't_type' => $lexeme['type']
							);
						}
					} else {
						// get info about the next lexeme
						$next_str = substr($str, strlen($lexeme['value']));
						$next_lexeme = $this->get_lexeme($next_str);
						// if next lexeme is delimiter or current lexeme is the last in the line
						// AND prev lexeme is delimiter or current lexeme is the first in the line
						if(($next_lexeme['delimiter'] === true || $offset + strlen($lexeme['value']) === $line_length) && ($prev_delimiter === true || $offset === 0)) {
							// put info about visible token to the result
							if ($lexeme['visible'] !== false) {
								$this->result[] = array(
									'row' => $line_number,
									'col' => $offset,
									't_value' => $lexeme['value'],
									't_type' => $lexeme['type']
								);
							}
						} else {
							// else current lexeme is not token 
							$prev_delimiter = false;
							$this->result[] = array(
								'row' => $line_number,
								'col' => $offset,
								't_value' => $lexeme['value'],
								't_type' => 'ERROR'
							);
						}
					}
					// increase offset by the length of found delimiter
					$offset += strlen($lexeme['value']);
				} else {
					// lexeme not found
					// put error symbol as error token to the result
					$this->result[] = array(
						'row' => $line_number,
						'col' => $offset,
						't_value' => $lexeme['value'],
						't_type' => 'ERROR'
					);
					$offset += 1;
				}
			}
		}
	}
	
	/**
	* Returns analysis result
	* 
	* Returns array with information about each token as:
	* Array
	* (
	*     [0] => Array
	*            (
	*                ['row'] => "0"
	*                ['col'] => "0"
	*                ['t_value'] => "and"
	*                ['t_type'] => "Multiplicative Operator"
	*            )
	*     ...
	* )
	*
	* @return array
	*/
	public function get_result() {
		return $this->result;
	}
	
	/**
	* Determines token presence in the beginning of the string
	* and returns information about the token found
	*
	* @param string $str string to be checked
	* @return array
	*/
	private function get_lexeme($str) {
		
		$result = array(
			'value' => '',
			'type' => '',
			'visible' => true,
			'delimiter' => false
		);
		
		// check for presence of each class of tokens
		foreach ($this->token_classes as $type => $property) {
			if (preg_match($property['pattern'], $str, $matches)) {
				// define the max. length matching substring as token
				// to avoid premature defining (e.g. '>' in '>=')
				if (strlen($matches[1]) > strlen($result['value'])) {
					$result['value'] = $matches[1];
					$result['type'] = $type;
					$result['visible'] = $property['visible'];
					$result['delimiter'] = $property['delimiter'];
				}
			}
		}
		
		// if nothing found, put the first symbol of $str to the value
		if ($result['value'] === '') {
			$result['value'] = $str[0];
		} else {
			// else if reserved words list is specified,
			// check whether the found token is reserved
			if (isset($this->reserved)) {
				$multiplicative = "and";
				foreach ($this->reserved as $type => $word) {
					//if value == and (multiplicative operator), show the type
					if (strcasecmp($result['value'], "and") == 0) {
						$result['type'] = $type;
					}
					//if value == reserved word and != "and", show "Reserved Word"
					if ((strcasecmp($result['value'], $word) == 0) and (strcasecmp($result['value'], "and")) != 0) {
						$result['type'] = 'Palavra Reservada';
					}
				}
			}
		}
		
		return $result;
		
	}
}