<!DOCTYPE html>
<html>

<body>
    <p>You have received the below enquiry:</p>
    <p>First Name: {{ is_object($fname) ? $fname->toString() : $fname }}</p>
    <p>Last Name: {{ is_object($lname) ? $lname->toString() : $lname }}</p>
    <p>Phone: {{ is_object($phone) ? $phone->toString() : $phone }}</p>
    <p>Email: {{ is_object($email) ? $email->toString() : $email }}</p>
    <p>Message: {{ is_object($message) ? $message->toString() : $message }}</p>
</body>

</html>