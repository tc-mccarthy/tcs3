<?php


/*
 * Functions for this plugin
 * Author: TC McCarthy
 * Aug 30, 2014
*/

function startsWith($haystack, $needle)
{
	return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle)
{
	return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}