<?php
/// a (hopefully) unbiased deterministic CSPRNG for non-power-of-two numbers
function hashPick($seed, $sizes) {
	$salt = 0;
	$hash = '';
	$remainingInShift = 0;
	$shift = 0;
	$res = [];
	foreach ($sizes as $size) {
		$numbits = 0;
		$max = $size;
		while ($max > 1) {
			$max >>= 1;
			$numbits += 1;
		}
		$mask = (1 << $numbits) - 1;
		do {
			// TODO: there may be a better seedable CSPRNG for PHP … I don't feel comfortable running our own crypto like this
			while ($remainingInShift < $numbits) {
				if (strlen($hash) < 1) {
					$hash = hash('sha512', $seed.';'.$salt, true);
					$salt += 1;
				}
				$shift = ($shift << 8) | ord($hash[0]);
				$hash = substr($hash, 1);
				$remainingInShift += 8;
			}
			$val = $shift & $mask;
			$shift >>= $numbits;
			$remainingInShift -= $numbits;
		} while ($val >= $size);
		array_push($res, $val);
	}
	return $res;
}

function shuffleIndices($seed, $size, $number) {
	$sizes = [];
	while ($number--) {array_push($sizes, $size--);}
	return hashPick($seed, $sizes);
}

function skyrimShuffle($seed, $number, $array) {
	$res = [];
	$crossout = [];
	foreach (shuffleIndices($seed, count($array), $number) as $idx) {
		foreach($crossout as $i) {if ($i <= $idx) {$idx += 1;}}
		array_push($crossout, $idx);
		sort($crossout, SORT_NUMERIC);
		array_push($res, $array[$idx]);
	}
	return $res;
}
