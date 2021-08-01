<?php

require_once('../db.php');
require_once('Task.php');
require_once('Response.php');

try{
	$writedb = db::connectwritedb();
	$readdb  = db::connectreaddb();
}
catch(PDOException $ex){
	error_log("connection error-".$ex, 0);
	$response = new Response();
	$response->sethttpStatusCode(500);
	$response->setSuccess(false);
	$response->addMessage("Data base connection error");
	$response->send();
	exit();
}

if(array_key_exists("taskid",$_GET)){

  $taskid = $_GET['taskid'];

    if($taskid == '' || !is_numeric($taskid)){
	    $response = new Response();
	    $response->sethttpStatusCode(400);
	    $response->setSuccess(false);
	    $response->addMessage("Task id should not be empty and must be numeric");
	    $response->send();
	    exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
			$query = $readdb->prepare('select id, name, author, DATE_FORMAT(dateofissue, "%d/%m/%Y %H:%i") as dateofissue,  DATE_FORMAT(dateofreturn, "%d/%m/%Y %H:%i") as dateofreturn, issuedby, contact from tblbooks where id = :taskid');
			$query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
			$query->execute();

            $rowcount = $query->rowcount();
            
            if($rowcount === 0){
			   $response = new Response();
	           $response->sethttpStatusCode(404);
	           $response->setSuccess(false);
	           $response->addMessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['name'], $row['author'], $row['dateofissue'], $row['dateofreturn'], $row['issuedby'], $row['contact']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Books info'] = $taskarray;
			
			$response = new Response();
	        $response->sethttpStatusCode(200);
	        $response->setSuccess(true);
	        $response->toCache(true);
			$response->setData($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
		    error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->sethttpStatusCode(500);
	        $response->setSuccess(false);
	        $response->addMessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->sethttpStatusCode(500);
	        $response->setSuccess(false);
	        $response->addMessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}
	
	if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
		try{
			$query = $readdb->prepare('delete from tbltasks where id = :taskid');
			$query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	

			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->addmessage("Task deleted successfully");
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}
		
	}
	
	if($_SERVER['REQUEST_METHOD'] === 'PATCH'){
		
		try{  
			 if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
			   $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
	           $response->addmessage("content type header not set to json");
	           $response->send();
	           exit(); 
			 }
			 
			 $rawpatchdata = file_get_contents('php://input');
			 
			 if(!$jsondata = json_decode($rawpatchdata)){
			   $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
	           $response->addmessage("Request body is not valid json");
	           $response->send();
	           exit(); 
			 }
			 
			 
			 $title_updated = false;
			 $description_updated = false;
			 $deadline_updated = false;
			 $completed_updated = false;
			 
			 $queryfield = "";
			 
			 if(isset($jsondata->title)){
				 $title_updated = true;
				 $queryfield .= "title = :title, ";
			 }
			 if(isset($jsondata->description)){
				 $description_updated = true;
				 $queryfield .= "description = :description, ";
			 }
			 if(isset($jsondata->deadline)){
				 $deadline_updated = true;
				 $queryfield .= "deadline = STR_TO_DATE(:deadline, '%d/%m/%Y %H:%i'), ";
			 }
			 if(isset($jsondata->completed)){
				 $completed_updated = true;
				 $queryfield .= "completed = :completed, ";
			 }
			  
			$queryfield = rtrim($queryfield, ", ");
			
			if($title_updated === false && $description_updated === false && $deadline_updated === false && $completed_updated === false){
			   $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
	           $response->addmessage("No task feild provided");
	           $response->send();
	           exit();
			}			   
			
			$query = $readdb->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :taskid');
			$query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
			}
			
			$querystring = "update tbltasks set ".$queryfield." where id = :taskid";
			$query = $writedb->prepare($querystring);
			
			
			if($title_updated == true){
				$task->settitle($jsondata->title);
				$up_title = $task->gettitle();
				$query->bindParam(':title', $up_title, PDO::PARAM_STR);
			}
			if($description_updated == true){
				$task->setdescription($jsondata->description);
				$up_description = $task->getdescription();
				$query->bindParam(':description', $up_description, PDO::PARAM_STR);
			}
			if($deadline_updated == true){
				$task->setdeadline($jsondata->deadline);
				$up_deadline = $task->getdeadline();
				$query->bindParam(':deadline', $up_deadline, PDO::PARAM_STR);
			}
			if($completed_updated == true){
				$task->setcompleted($jsondata->completed);
				$up_completed = $task->getcompleted();
				$query->bindParam(':completed', $up_completed, PDO::PARAM_STR);
			}
			$query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
			$query->execute();
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			$query = $readdb->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :taskid');
			$query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
			$query->execute();
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->addmessage("Task updated");
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("failed to update task check your data for errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(400);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}
		
		
	}
    else{
		$response = new Response();
	    $response->httpstatuscode(405);
	    $response->setsuccess(false);
	    $response->addmessage("Request method not allowed");
	    $response->send();
	    exit(); 
	}

}


if(array_key_exists("name",$_GET)){

  $name = $_GET['name'];
  $name = strtolower($name);
  
    if($name == '' || is_numeric($name)){
	    $response = new Response();
	    $response->sethttpStatusCode(400);
	    $response->setSuccess(false);
	    $response->addMessage("name should not be empty and must be non numeric");
	    $response->send();
	    exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
			$query = $readdb->prepare('select id, name, author, DATE_FORMAT(dateofissue, "%d/%m/%Y %H:%i") as dateofissue,  DATE_FORMAT(dateofreturn, "%d/%m/%Y %H:%i") as dateofreturn, issuedby, contact from tblbooks where name = :name');
			$query->bindParam(':name', $name, PDO::PARAM_STR);
			$query->execute();

            $rowcount = $query->rowcount();
            
            if($rowcount === 0){
			   $response = new Response();
	           $response->sethttpStatusCode(404);
	           $response->setSuccess(false);
	           $response->addMessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['name'], $row['author'], $row['dateofissue'], $row['dateofreturn'], $row['issuedby'], $row['contact']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Books info'] = $taskarray;
			
			$response = new Response();
	        $response->sethttpStatusCode(200);
	        $response->setSuccess(true);
	        $response->toCache(true);
			$response->setData($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
		    error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->sethttpStatusCode(500);
	        $response->setSuccess(false);
	        $response->addMessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->sethttpStatusCode(500);
	        $response->setSuccess(false);
	        $response->addMessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}
}

elseif(array_key_exists("completed",$_GET)){
	
	$completed = $_GET['completed'];
	
	if($completed !== 'Y' && $completed !== 'N'){
		$response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    $response->addmessage("completed status must be Y or N");
	    $response->send();
	    exit();
	}
	if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
			$query = $readdb->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where completed = :completed');
			$query->bindParam(':completed', $completed, PDO::PARAM_STR);
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}

}  

elseif(array_key_exists("page",$_GET)){

	if($_SERVER['REQUEST_METHOD'] == 'GET'){
		
		$page = $_GET['page'];
		
		if($page == '' || !is_numeric($page)){
		    $response = new Response();
	        $response->httpstatuscode(400);
	        $response->setsuccess(false);
	        $response->addmessage("page number cannont be blank and must be numeric");
	        $response->send();
	        exit();
		}
		$limitperpage = 10;
		
		
		try{
			$query = $readdb->prepare('select count(id) as totalnooftask from tbltasks');
			$query->execute();
            
			$row = $query->fetch(PDO::FETCH_ASSOC);
			
			$taskcount = intval($row['totalnooftask']);
			
			$numofpages = ceil($taskcount/$limitperpage);
			
            $rowcount = $query->rowcount();
			
			if($numofpages === 0){
				$numofpages = 1;
			}
			
			if($page > $numofpages || $page == 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("page not found");
	           $response->send();
	           exit(); 
			}

            $offset = ($page == 1 ? 0 : ($limitperpage*($page-1)));
            $query = $readdb->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks limit :pglimit offset :offset');
			$query->bindParam(':pglimit', $limitperpage, PDO::PARAM_INT);
			$query->bindParam(':offset', $offset, PDO::PARAM_INT);
			$query->execute();
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['total_rows'] = $taskcount;
			$returndata['total_page'] = $numofpages;
			$returndata['has_next_page'] = $page < $numofpages;
			$returndata['has_previous_page'] = $page > 1;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("failed to get page");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}
			
	else{
		$response = new Response();
	    $response->httpstatuscode(500);
	    $response->setsuccess(false);
	    $response->addmessage("Data base connection error");
	    $response->send();
	    exit();
	}

}

elseif(empty($_GET)){
	
	if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
			$query = $readdb->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks');
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}
	
	elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
		try{
			if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
			  $response = new Response();
	          $response->httpstatuscode(400);
	          $response->setsuccess(false);
	          $response->addmessage("content type header is not set to json");
	          $response->send();
	          exit(); 
			}
			
			$rawpostdata = file_get_contents('php://input');
			
			if(!$jsondata = json_decode($rawpostdata)){
			  $response = new Response();
	          $response->httpstatuscode(400);
	          $response->setsuccess(false);
	          $response->addmessage("Request body is not valid json");
	          $response->send();
	          exit(); 
			}
			
			if(!isset($jsondata->title) || !isset($jsondata->completed)){
			  $response = new Response();
	          $response->httpstatuscode(400);
	          $response->setsuccess(false);
	          (!isset($jsondata->title) ? $response->addmessage("Title feild is mandatory and must be provided") : false);
			  (!isset($jsondata->completed) ? $response->addmessage("completed feild is mandatory and must be provided") : false);
	          $response->send();
	          exit(); 
			}
			
			$newtask = new Task(null, $jsondata->title, (isset($jsondata->description) ? $jsondata->description : null), (isset($jsondata->deadline) ? $jsondata->deadline : null), $jsondata->completed);
			
			$title = $newtask->gettitle();
			$description = $newtask->getdescription();
			$deadline = $newtask->getdeadline();
			$completed = $newtask->getcompleted();
			echo $jsondata->title;
			$query = $writedb->prepare('insert into tbltasks (title, description, deadline, completed) values (:title, :description, STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), :completed)');
			$query->bindParam(':title', $title, PDO::PARAM_STR);
			$query->bindParam(':description', $description, PDO::PARAM_STR);
			$query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
			$query->bindParam(':completed', $completed, PDO::PARAM_STR);
			$query->execute();
			
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}
			
			$lasttaskid = $writedb->lastinsertid();
			
			$query = $readdb->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :taskid');
			$query->bindParam(':taskid', $lasttaskid, PDO::PARAM_STR);
			$query->execute();
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("failed to insert task into database check subbmited data for error");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(400);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}
		
	}
	
	else{
		$response = new Response();
	    $response->httpstatuscode(405);
	    $response->setsuccess(false);
	    $response->addmessage("Request method not allowed");
	    $response->send();
	    exit();
	}

}

else{
	$response = new Response();
	$response->httpstatuscode(404);
	$response->setsuccess(false);
	$response->addmessage("end point not found");
	$response->send();
	exit();
}
















