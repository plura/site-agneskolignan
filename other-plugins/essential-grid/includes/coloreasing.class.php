<?php
/**
* @author    ThemePunch <info@themepunch.com>
* @link      https://www.themepunch.com/
* @copyright 2023 ThemePunch
*/

if (!defined('ABSPATH')) exit();


class ESGColorEasing
{

	public function __construct()
	{
	}

	/**
	 * get the distance between two r/g/b values
	 * @since 6.0
	 */
	public static function distColor($px, $bx, $ex, $bv, $ev)
	{
		$num = abs(((($px - $bx) / ($ex - $bx)) * ($ev - $bv)) + $bv);
		$num = round($num);
		$num = min($num, 255);
		return max($num, 0);
	}

	/**
	 * get the distance between two alpha values
	 * @since 6.0
	 */
	public static function distAlpha($px, $bx, $ex, $bv, $ev)
	{
		$bv = floatval($bv);
		$num = floatval((($px - $bx) / ($ex - $bx)) * ($ev - $bv));
		$num = number_format($num, 2, '.', '');
		$num = abs($num + $bv);
		$num = min($num, 1);
		return max($num, 0);
	}

	/**
	 * insert easing colors to a gradient
	 * @since 6.0
	 */
	public static function insertPoints($start, $end, &$ar, $easing, $strength)
	{
		$startPos = $start['position'];
		$endPos = $end['position'];

		if ($startPos > $endPos) return;

		$positions = array();

		for ($i = 0; $i < $strength; $i++) {

			$val = RSColorEasing::easing($i, 0, 1, $strength, $easing);
			$val = floatval($val);
			$val = number_format($val, 2, '.', '');
			$val = $val * ($endPos - $startPos) + $startPos;
			if ($val > $startPos && $val < $endPos) $positions[] = $val;

		}

		$len = count($positions);
		$num = floatval(($endPos - $startPos) / ($len + 1));
		$count = number_format($num, 2, '.', '');
		$p = $count + $startPos;

		for ($i = 0; $i < $len; $i++) {

			$px = $positions[$i];
			if ($px === $start['position']) continue;

			$r = RSColorEasing::distColor($px, $startPos, $endPos, $start['r'], $end['r']);
			$g = RSColorEasing::distColor($px, $startPos, $endPos, $start['g'], $end['g']);
			$b = RSColorEasing::distColor($px, $startPos, $endPos, $start['b'], $end['b']);
			$a = RSColorEasing::distAlpha($px, $startPos, $endPos, $start['a'], $end['a']);

			$startA = RSColorpicker::sanitizeAlpha($start['a']);
			$endA = RSColorpicker::sanitizeAlpha($end['a']);

			$point = array(

				'position' => $p,
				'r' => $start['r'] !== $end['r'] ? round($r) : $start['r'],
				'g' => $start['g'] !== $end['g'] ? round($g) : $start['g'],
				'b' => $start['b'] !== $end['b'] ? round($b) : $start['b'],
				'a' => $startA !== $endA ? RSColorpicker::sanitizeAlpha($a) : $startA

			);

			$p += $count;
			$p = number_format(floatval($p), 2, '.', '');
			$ar[] = $point;
		}
	}

	/**
	 * easing equations
	 * @since 6.0
	 */
	public static function easing($n, $t, $e, $u, $ease = 'sine.easeinout')
	{
		$easing = array('sine, easeinout');
		if (is_string($ease) && strpos($ease, '.') !== false) {
			$ease = explode('.', $ease);
			if (count($ease) === 2) $easing = [$ease[0], $ease[1]];
		}

		switch ($easing[0]) {

			case 'quint':
				switch ($easing[1]) {
					case 'easein':
						return $e * (($n = $n / $u - 1) * $n * $n * $n * $n + 1) + $t;
						
					case 'easeout':
						return $e * ($n /= $u) * $n * $n * $n * $n + $t;
						
					case 'easeinout':
						return ($n /= $u / 2) < 1 ? $e / 2 * $n * $n * $n * $n * $n + $t : $e / 2 * (($n -= 2) * $n * $n * $n * $n + 2) + $t;
						
				}
				break;
				
			case 'quad':
				switch ($easing[1]) {
					case 'easein':
						return $e * ($n /= $u) * $n + $t;
						
					case 'easeout':
						return -$e * ($n /= $u) * ($n - 2) + $t;
						
					case 'easeinout':
						return ($n /= $u / 2) < 1 ? $e / 2 * $n * $n + $t : -$e / 2 * (--$n * ($n - 2) - 1) + $t;
						
				}
				break;

			case 'quart':
				switch ($easing[1]) {
					case 'easein':
						return $e * ($n /= $u) * $n * $n * $n + $t;
						
					case 'easeout':
						return -$e * (($n = $n / $u - 1) * $n * $n * $n - 1) + $t;
						
					case 'easeinout':
						return ($n /= $u / 2) < 1 ? $e / 2 * $n * $n * $n * $n + $t : -$e / 2 * (($n -= 2) * $n * $n * $n - 2) + $t;
						
				}
				break;

			case 'cubic':
				switch ($easing[1]) {
					case 'easein':
						return $e * ($n /= $u) * $n * $n + $t;
						
					case 'easeout':
						return $e * (($n = $n / $u - 1) * $n * $n + 1) + $t;
						
					case 'easeinout':
						return ($n /= $u / 2) < 1 ? $e / 2 * $n * $n * $n + $t : $e / 2 * (($n -= 2) * $n * $n + 2) + $t;
						
				}
				break;

			case 'circ':
				switch ($easing[1]) {
					case 'easein':
						return -$e * (sqrt(1 - ($n /= $u) * $n) - 1) + $t;
						
					case 'easeout':
						return $e * sqrt(1 - ($n = $n / $u - 1) * $n) + $t;
						
					case 'easeinout':
						return ($n /= $u / 2) < 1 ? -$e / 2 * (sqrt(1 - $n * $n) - 1) + $t : $e / 2 * (sqrt(1 - ($n -= 2) * $n) + 1) + $t;
						
				}
				break;

			case 'expo':
				switch ($easing[1]) {
					case 'easein':
						return 0 === $n ? $t : $e * pow(2, 10 * ($n / $u - 1)) + $t;
						
					case 'easeout':
						return $n === $u ? $t + $e : $e * (1 - pow(2, -10 * $n / $u)) + $t;
						
					case 'easeinout':
						if (0 === $n) {
							return $t;
						} elseif ($n === $u) {
							return $t + $e;
						} elseif (($n /= $u / 2) < 1) {
							return $e / 2 * pow(2, 10 * ($n - 1)) + $t;
						} else {
							return $e / 2 * (2 - pow(2, -10 * --$n)) + $t;
						}
						
				}
				break;

			case 'bounce':
				switch ($easing[1]) {
					case 'easein':
						return $e - RSColorEasing::easing($u - $n, 0, $e, $u, 'bounce.easeout') + $t;
						
					case 'easeout':
						if (($n /= $u) < (1 / 2.75)) {
							return $e * (7.5625 * $n * $n) + $t;
						} else if ($n < (2 / 2.75)) {
							return $e * (7.5625 * ($n -= (1.5 / 2.75)) * $n + 0.75) + $t;
						} else if ($n < (2.5 / 2.75)) {
							return $e * (7.5625 * ($n -= (2.25 / 2.75)) * $n + 0.9375) + $t;
						} else {
							return $e * (7.5625 * ($n -= (2.625 / 2.75)) * $n + 0.984375) + $t;
						}
						
					case 'easeinout':
						if ($n < $u / 2) {
							return RSColorEasing::easing($n * 2, 0, $e, $u, 'bounce.easein') * 0.5 + $t;
						} else {
							return RSColorEasing::easing($n * 2 - $u, 0, $e, $u, 'bounce.easeout') * 0.5 + $e * 0.5 + $t;
						}
						
				}
				break;

			default:
				switch ($easing[1]) {
					case 'easein':
						return -$e * cos($n / $u * (M_PI / 2)) + $e + $t;
						
					case 'easeout':
						return $e * sin($n / $u * (M_PI / 2)) + $t;
						
					default:
						return -$e / 2 * (cos(M_PI * $n / $u) - 1) + $t;
				}
		}
		return 0;
	}

}
