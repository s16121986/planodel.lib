<?php

interface Controller_Router_Route_Interface {
	
    public function match($path);
	
    public function assemble($data = array(), $reset = false, $encode = false);
	
    public static function getInstance(Config $config);
	
}

