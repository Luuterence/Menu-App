<?php
/**
 * enquiries.php — Admin entry point for viewing contact messages
 * Delegates all logic to contact.php's adminEnquiriesView()
 */
$_GET['admin'] = '1';
require_once 'contact.php';
