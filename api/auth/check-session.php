<?php
session_start();
header('Content-Type: application/json');
// we stage the headers to allow return of only data in json not in html format that will be bad if it does 

// we first check if the user's id/session is already stored in the db 
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session'
    ]); 
    exit; // we give a feedback and exit if we dont have any user in session with that id
}

// then if we have any (user id stored in session) we go proceed to display tyeh user's data and a success messahge of confirmation and yes we encode it in json formt  making sure the user's id, name and role are well aligned to make surer they are presented the coreect ui amd previgilagies accodingly especially in the dashboard side...

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $_SESSION['user']['id'],
        'name' => $_SESSION['user']['name'],
        'role' => $_SESSION['user']['role']
    ]
]);