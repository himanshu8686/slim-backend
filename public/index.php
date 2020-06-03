<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require ('../vendor/autoload.php');
require '../includes/DbConnect.php';
require '../includes/DbOperations.php';

$app = new \Slim\App([
    'settings'=>[
        'displayErrorDetails'=>true
    ]
]);

$app->get('/hello/{name}', function (Request $request, Response $response,array $args) {
   // $name = $request->getAttribute('name');
   $name= $args['name'];
    $response->getBody()->write("Hello, $name");
    $db=new DbConnect;
    if($db ->connect()!=null)
    {
        echo ' Connection Successful';
        $var="hello";
        $output=$var."world";
        echo $output;
    }
    else
       { 
        echo 'Connection failed';
       } 
    return $response;
});

/**
 *  end point:createuser
 *  parameters:email,password,name,school
 *  method :POST
 */
    $app->post('/createuser',function(Request $request,Response $response)
    {
        if(!haveEmptyParameters(array('email','password','name','school'),$request,$response))
        {
            $request_data=$request->getParsedBody();
            $email = $request_data['email'];
            $password = $request_data['password'];
            $name = $request_data['name'];
            $school = $request_data['school'];

            $dbOperation=new DbOperations;
            $result = $dbOperation->createUser($email,$password,$name,$school);

            if($result == USER_CREATED)
            {
                $message = array();
                $message['error'] = false;
                $message['message'] = 'User created successfully';

                $response->write(json_encode($message));

                return $response
                                ->withHeader('Content-type','application/json')
                                ->withStatus(201);
            }
            elseif($result==USER_FAILURE)
            {
                $message = array();
                $message['error'] = true;
                $message['message'] = 'Some Error occured';

                $response->write(json_encode($message));

                return $response
                                ->withHeader('Content-type','application/json')
                                ->withStatus(422);
            }
            elseif($result==USER_EXISTS)
            {
                $message = array();
                $message['error'] = true;
                $message['message'] = 'User already Exists!';

                $response->write(json_encode($message));

                return $response
                                ->withHeader('Content-type','application/json')
                                ->withStatus(422);
            }
        }
        return $response
                            ->withHeader('Content-type','application/json')
                            ->withStatus(422);
    }
    );

    /**
     * 
     */
    function haveEmptyParameters($required_params,$request,$response)
    {
        $error=false;
        $error_params='';
        $request_params=$request->getParsedBody(); //$_REQUEST is an array

    // print("Request params ".implode(" ",$request_params));

        foreach ($required_params as $param)
        {
            if(!isset($request_params[$param]) || strlen($request_params[$param]) <=0 )
            {
                $error=true;
                $error_params.=$param.', ';
            }
        }

        if($error)
        {
            // creating an array
            $error_detail=array();
            $error_detail['error']=true;
            $error_detail['message']='Required parameters '.substr($error_params,0,-2) . ' are missing or empty';
            $response->write(json_encode($error_detail));
        }
        return $error;
    }

/**
 *  end point:userlogin
 *  parameters:email,password
 *  method :POST
 */
    $app->post('/userlogin',function(Request $request,Response $response)
    {
        if(!haveEmptyParameters(array('email','password'),$request,$response))
        {
            $request_data=$request->getParsedBody();
            $email = $request_data['email'];
            $password = $request_data['password'];

            $dbOperation=new DbOperations;
           $result= $dbOperation->userLogin($email,$password);
           if($result==USER_AUTHENTICATED)
           {
                $user=$dbOperation->getUserByEmail($email);
                $response_data=array();

                $response_data['error']=false;
                $response_data['message']='Login Successful';
                $response_data['user']=$user;

                $response->write(json_encode($response_data));
                return $response
                                ->withHeader('Content-type','application/json')
                                ->withStatus(200);
           }   
           elseif($result==USER_NOT_FOUND)
           {
                $response_data=array();

                $response_data['error']=true;
                $response_data['message']='User not exists';

                $response->write(json_encode($response_data));
                return $response
                                ->withHeader('Content-type','application/json')
                                ->withStatus(404);
           }   
           elseif($result == USER_AUTHENTICATION_FAILED)
           {
                $response_data=array();

                $response_data['error']=true;
                $response_data['message']='Invalid Credentials';

                $response->write(json_encode($response_data));
                return $response
                                ->withHeader('Content-type','application/json')
                                ->withStatus(400);
           }      
        }
        return $response
                            ->withHeader('Content-type','application/json')
                            ->withStatus(422);
    }
    );

/**
 *  end point:userlogin
 *  parameters:email,password
 *  method :GET
 */
$app->get('/allusers',function(Request $request,Response $response)
{
   $dbOperation= new DbOperations;
   $all_users=$dbOperation->getAllUsers();
   $response_data=array();
   $response_data['error']=false;
   $response_data['users']=$all_users;
   $response->write(json_encode($response_data));
   return $response
                    ->withHeader('Content-type','application/json')
                    ->withStatus(200);
});

/**
 *  end point:updateuser/{id}
 *  parameters:email,password
 *  method :PUT
 */
    $app->put('/updateuser/{id}',function(Request $request,Response $response,array $args)
    {
        $id=$args['id'];
        if(!haveEmptyParameters(array('email','name','school'),$request,$response))
        {
           $request_data= $request->getParsedBody();
           $email = $request_data['email'];
           $name = $request_data['name'];
           $school = $request_data['school'];
           $dbOperation=new DbOperations;
           if($dbOperation->updateUser($email,$name,$school,$id))
           {
               $response_data=array();
               $response_data['error']=false;
               $response_data['message']='user updated successfully!';

               $user=$dbOperation->getUserByEmail($email);
               $response_data['user']=$user;
               $response->write(json_encode($response_data));
               return $response
               ->withHeader('Content-type','application/json')
               ->withStatus(200);
           }
           else{
            $response_data=array();
            $response_data['error']=true;
            $response_data['message']='Updation failed !! please try again later!';

            $user=$dbOperation->getUserByEmail($email);
            $response_data['user']=$user;
            $response->write(json_encode($response_data));
            return $response
            ->withHeader('Content-type','application/json')
            ->withStatus(200);
           }
        }
    });

    $app->put('/updatepassword/{id}',function(Request $request,Response $response,array $args)
    {
        $id=$args['id'];
        if(!haveEmptyParameters(array('currentpassword','newpassword','email'),$request,$response))
        {
            $request_data= $request->getParsedBody();
            $currentpassword=$request_data['currentpassword'];
            $newpassword=$request_data['newpassword'];
            $email = $request_data['email'];
            $dbOperation=new DbOperations;
            $result=$dbOperation->updatePassword($currentpassword,$newpassword,$email);
            if ($result==PASSWORD_CHANGED) {
                $response_data=array();
                $response_data['error']=false;
                $response_data['message']='Password changed';

                $response->write(json_encode($response_data));
                return $response
                ->withHeader('Content-type','application/json')
                ->withStatus(200);
            }
            elseif($result==PASSWORD_DO_NOT_MATCH)
            {
                $response_data=array();
                $response_data['error']=true;
                $response_data['message']='You have given wrong password';

                $response->write(json_encode($response_data));
                return $response
                ->withHeader('Content-type','application/json')
                ->withStatus(200);
            }
            elseif($result==PASSWORD_NOT_CHANGED)
            {
                $response_data=array();
                $response_data['error']=true;
                $response_data['message']='Some error occured';

                $response->write(json_encode($response_data));
                return $response
                            ->withHeader('Content-type','application/json')
                            ->withStatus(200);
            }
        }

        return $response
        ->withHeader('Content-type','application/json')
        ->withStatus(422);
    });

    /**
     * 
     */
    $app->delete('/deleteuser/{id}',function(Request $request,Response $response,array $args)
    {
        $id=$args['id'];
        $dbOperation=new DbOperations;
        $response_data=array();
        if($dbOperation->deleteUser($id))
        {
            $response_data['error']=false;
            $response_data['message']='User has been deleted!';

        }
        else{
            $response_data['error']=true;
            $response_data['message']='Please try again later!';
        }
        $response->write(json_encode($response_data));
        return $response
                        ->withHeader('Content-type','application/json')
                        ->withStatus(200);
    });
$app->run();