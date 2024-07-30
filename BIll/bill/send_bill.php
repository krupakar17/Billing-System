<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bill";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['email'])) {
    $recipient_email = $_POST['email'];
    
    // Prepare email content
    $subject = "Your Bill Details";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: your-email@example.com" . "\r\n"; // Change this to your email address
    
    // Create email body
    $email_body = "<html><body>";
    $email_body .= "<h2>Bill Details</h2>";

    // Fetch bill details from the database with product names
    $sql = "SELECT bil_temp.p_id, bil_temp.qty, bil_temp.price, product_details.product_name 
            FROM bil_temp 
            JOIN product_details ON bil_temp.p_id = product_details.pid";
    $result = mysqli_query($conn, $sql);

    $total_value = 0;

    if (mysqli_num_rows($result) > 0) {
        $email_body .= "<table border='1' cellpadding='10' cellspacing='0'>";
        $email_body .= "<tr><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Price</th><th>Total Price</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            $item_total = $row['qty'] * $row['price'];
            $total_value += $item_total;
            $email_body .= "<tr>";
            $email_body .= "<td>" . $row['p_id'] . "</td>";
            $email_body .= "<td>" . $row['product_name'] . "</td>";
            $email_body .= "<td>" . $row['qty'] . "</td>";
            $email_body .= "<td>" . $row['price'] . "</td>";
            $email_body .= "<td>" . $item_total . "</td>";
            $email_body .= "</tr>";
        }
        
        $email_body .= "</table>";

        // Add total value to the email body
        $email_body .= "<p><strong>Total Value:</strong> Rs." . number_format($total_value, 2) . "</p>";
    } else {
        $email_body .= "<p>No bill details found.</p>";
    }
    
    $email_body .= "</body></html>";

    // Send email
    if (mail($recipient_email, $subject, $email_body, $headers)) {
        echo '<script>alert("Bill sent successfully!"); window.location.href = "bmid.html";</script>';
    } else {
        echo '<script>alert("Failed to send bill."); window.location.href = "bmid.html";</script>';
    }
} else {
    echo '<script>alert("No email address provided."); window.location.href = "bmid.html";</script>';
}

// Close connection
mysqli_close($conn);
?>
