<?php

class Taskexception extends Exception {}

class task{
	
	
	public function __construct($id, $name, $author, $dateofissue, $dateofreturn, $issuedby, $contact){
		$this->setid($id);
		$this->setname($name);
		$this->setdateofissue($dateofissue);
		$this->setdateofreturn($dateofreturn);
		$this->setauthor($author);
		$this->setissuedby($issuedby);
		$this->setcontact($contact);
	}
	
	private $_id;
	private $_name;
	private $_dateofissue;
	private $_dateofreturn;
	private $_author;
	private $_issuedby;
	private $_contact;
	
	public function getid(){
		return $this->_id;
	}
	public function getname(){
		return $this->_name;
	}
	
	public function getdateofissue(){
		return $this->_dateofissue;
	}
	
	public function getdateofreturn(){
		return $this->_dateofreturn;
	}
	
	public function getauthor(){
		return $this->_author;
	}
	
	public function getissuedby(){
		return $this->_issuedby;
	}
	
	public function getcontact(){
		return $this->_contact;
	}
	
	
	
	public function setid($id){
		if(($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372836854775807 || $this->_id !== null)){
			throw new Taskexception("Task id error");
		}
		$this->_id = $id;
	}
	
	public function setname($name){
		if(strlen($name) < 0 || strlen($name) > 255){
			throw new Taskexception("book name error");
		}
		$this->_name = $name;
	}
	
	public function setdateofissue($dateofissue){
		if(($dateofissue != null) && date_format(date_create_from_format('d/m/Y H:i', $dateofissue), 'd/m/Y H:i') != $dateofissue){
			throw new Taskexception("date of issue error");
		}
		$this->_dateofissue = $dateofissue;
	}
	
	public function setdateofreturn($dateofreturn){
		if(($dateofreturn !== null) && date_format(date_create_from_format('d/m/Y H:i', $dateofreturn), 'd/m/Y H:i') != $dateofreturn){
			throw new Taskexception("Task deadline error");
		}
		$this->_dateofreturn = $dateofreturn;
	}
	
	public function setauthor($author){
		if(strlen($author) < 0 || strlen($author) > 255){
			throw new Taskexception("author name error");
		}
		$this->_author = $author;
	}
	
	public function setissuedby($issuedby){
		if(strlen($issuedby) < 0 || strlen($issuedby) > 255 && $issuedby !== null){
			throw new Taskexception("issued by error");
		}
		$this->_issuedby = $issuedby;
	}
	
	public function setcontact($contact){
		if(strlen($contact) < 0 || strlen($contact) > 11 && $contact !== null){
			throw new Taskexception("contact error");
		}
		$this->_contact = $contact;
	}
	
	
      public function returntaskarray(){
    	 $task = array();
		 $task['id'] = $this->getid();
		 $task['Name'] = $this->getname();
		 $task['Author'] = $this->getauthor();
		 $task['date of issue'] = $this->getdateofissue();
		 $task['date of return'] = $this->getdateofreturn();
		 $task['Issued by'] = $this->getissuedby();
		 $task['contact'] = $this->getcontact();
		 
		  return $task;
	    }
		 
}
	
	
	
	
	