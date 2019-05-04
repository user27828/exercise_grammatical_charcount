#!/usr/bin/php
<?PHP
/**
 *	Exercise algorithm: 1-1000 character count
 *	Objectives:
 *		1. Count characters in grammatical numbers from 1 to 1000 (inclusive)
 *		2. Include "and" in compliance with British usage
 *		3. Strip spaces and hyphens
 *
 *	Result of 1-1000: 21124 letters.
 *
 *	Usage: PHP CLI - ensure permissions to execute on a *nix system.  Modify #!/usr/bin/php if needed.
 *		(with #! php path):		<path-to-file>/thisfile.php
 *		(without #!):			php <path-to-file>/thisfile.php
 *
 *	Arguments: 1 - Start number, 2 - End Number
 *		<path-to-file>/thisfile.php 1 1000
 *		<path-to-file>/thisfile.php 90 150
 *	
 *	Limitations:	This can count up to 999,999,999.  Higher is possible on 64-bit systems or
 *					when using bcmath functions.
 *
 *	@author Marc Stephenson /  GitHub: user27828
 */
final class CharacterCount {
	private		$count_start	= 1;
	private		$count_end		= 1000;
	private		$_debug			= TRUE;	// Debug turns on printing/filtering of the grammatical numbers
	private		$_count_chars	= 0;

	// Spoken/written grammatical numbers to 19.  Array indexes represent the number
	private		$_SINGLE_NINETEEN	= array(
		'', // <- We do not say/spell zero in normal grammar, at least for the purpose of this demo
		'one', 'two', 'three', 'four', 'five', 'six', 'seven',
		'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 
		'seventeen', 'eighteen', 'nineteen'
	);

	// twenty to ninety
	private		$_TENS				= array(
		20		=> 'twenty',
		30		=> 'thirty',
		40		=> 'forty',
		50		=> 'fifty',
		60		=> 'sixty',
		70		=> 'seventy',
		80		=> 'eighty',
		90		=> 'ninety'
	);

	// Hundreds to millions - this can be expanded, but as-is, we won't have to worry
	// about 32-bit systems and using bcmath to support larger numbers
	private		$_HUNDRED_PLUS		= array(
		1000 => 'thousand', 1000000 => 'million'
	);

	/**
	 *	Constructor
	 */
	public function __construct() {
		$this->count_start		=(integer) (isset($GLOBALS['argv']) && array_key_exists(1, $GLOBALS['argv'])) 
			? $GLOBALS['argv'][1] : $this->count_start;
		$this->count_end		=(integer) (isset($GLOBALS['argv']) && array_key_exists(2, $GLOBALS['argv'])) 
			? $GLOBALS['argv'][2] : $this->count_end;
		
		return $this->run();
	}

	/**
	 *	Output status
	 * @param string|array	$status		- Status message
	 * @param boolean 		$exit		- Exit script after printing status?
	 */
	public function echo_status($status, $exit=FALSE) {
		$status		= !is_array($status) ? $status : implode("\n", $status);
		echo $status . "\n";
		if( $exit ) {
			echo "Quitting...\n\n";
			exit;
		}
	}

	/**
	 *	Get the grammatical number from the integer value
	 * Values from 1-99 have unique logic applied to their grammar, so we handle them in a different
	 *	way versus numbers at 100+.
	 * @param integer	$x			- Integer
	 * @return string				- Grammatical number
	 */
	public function get_gram_number($x) {
		$x				=(integer) $x;
		$val			= '';	// Return value
		$x_str			=(string) $x;
		$X_ARRAY		= str_split($x_str);
		switch(TRUE) {
			case ($x<20):		// Single: 0-19
				$val		= $this->_SINGLE_NINETEEN[$x];
				break;
			case ($x<100):		// Tens: 20-99
				$tens_key	=(integer) $X_ARRAY[0] . '0';
				$single_key	=(integer) $X_ARRAY[1];
				// Only add dash when the singles value is NOT 0
				$dash		= ($single_key===0) ? '' : '-';
				$val		= $this->_TENS[$tens_key] . $dash . $this->get_gram_number($single_key);
				break;
			case ($x<1000):		// Hundreds: 100-999 - first digit multipliers are only single digits, unlike thousands+
				$multiplier	= $this->get_gram_number((integer)$X_ARRAY[0]);
				$tens		= $this->get_gram_number((integer)substr($x_str, 1));	// Get number without hundreds place
				$and		= empty($tens) ? '' : ' and ';
				$val		= $multiplier . ' hundred' . $and . $tens;
				break;
			case ($x>=1000):
				$VAL		= array();
				// Iterate the _HUNDRED_PLUS array in reverse order
				foreach(array_reverse($this->_HUNDRED_PLUS, TRUE) AS $min => $name) {
					$x				=(integer) isset($last) ? $last : $x;
					if( $min > $x ) { continue; }	// Skip this scale, it is higher than our number
					// Length of $min value -1.  So, 1000000 (7) becomes 6, and allows us to process lower values separately
					$scale_digits	= strlen($min)-1;
					$first			= substr((string)$x, 0, strlen($x)-$scale_digits);	// Digits within this scale
					$last			= substr((string)$x, strlen($first));				// Digits outside (after) this scale
					/*$this->echo_status('x:            ' . $x);
					$this->echo_status('scale digits: ' . $scale_digits);
					$this->echo_status('first:        ' . $first);
					$this->echo_status('last:         ' . $last);*/
					$VAL[]			= $this->get_gram_number($first) . ' ' . $name;
					if( intval($last)>0 && intval($last)<1000 ) {
						$VAL[]			= $this->get_gram_number($last);
					}	// End IF
				}	// End Foreach
				if( !empty($VAL) ) {
					$val	= implode(' ', $VAL);
				}
				break;
		}
		return $val;
	}

	/**
	 *	Main
	 */
	public function run() {
		// Run disqualifying checks
		// Get the last value in _HUNDRED_PLUS, this will be used to make sure the user is within bounds
		$last_hundred_plus		= array_keys($this->_HUNDRED_PLUS)[count($this->_HUNDRED_PLUS)-1];
		if( $this->count_start < 0 || $this->count_start > $this->count_end 
			// Easy way to check for the maximum possible number based on the minimum number of a 
			// number system scale
			|| strlen((string)$this->count_end) > strlen((string)$last_hundred_plus) 
		) {
			$this->echo_status(sprintf('Invalid start (%d) or end (%d) number.', 
				$this->count_start, $this->count_end), TRUE);
		}
		
		$this->echo_status(sprintf(
			'+ Count grammatical number characters from %d to %d.  Stripping dashes, and counting "and".', 
			$this->count_start, $this->count_end));
		for($i=$this->count_start; $i<=$this->count_end; $i++) {
			$gram_number			= $this->get_gram_number($i);
			$gram_number_clean		= str_replace(array('-', ' '), '', $gram_number);
			$gram_number_clean_len	= strlen($gram_number_clean);
			$this->_count_chars	   += $gram_number_clean_len;
			if( $this->_debug ) {
				$this->echo_status(sprintf('[DBG]+ (%d) Grammatical Int/Clean: "%s" / "%s"', 
					$i, $gram_number, $gram_number_clean));
				$this->echo_status(sprintf('[DBG] -Length:                     "%s" (Total: %d)', 
					$gram_number_clean_len, $this->_count_chars));
			}
		}	// End Foreach
				$this->echo_status(sprintf('+Total characters:            %d', $this->_count_chars));
				$this->echo_status('', TRUE);	// Quit
	}
}	// End Class CharacterCount

// Instantiate class.  It will auto-run via class constructor
$Run	= new CharacterCount();
?>