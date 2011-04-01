<?php
/**
 * @package Core
 * @subpackage model.enum
 */ 
interface DropFolderStatus extends BaseEnum
{
	const DISABLED  = 0;
	const AUTOMATIC = 1;
	const MANUAL    = 2;	
}