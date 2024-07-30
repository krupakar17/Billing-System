<?php
date_default_timezone_set("Asia/Calcutta");
$month = date('F');
$hourMin = date('h:i');
$dte = date('d-m-Y');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bill";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$d = 0; // discount
$day = date('D');
$q = 0;
$qt = "";
$amount = "";
$prodid = "";
$tot = 0;
$lowStockProducts = [];

$result = mysqli_query($conn, "SELECT * FROM bil_temp");

while ($row = mysqli_fetch_array($result)) {
    if ($prodid == "") {
        $p = $row['p_id'];
        $qsingle = $row['qty'];
        $qt = $row['qty'];
        $billno = $row['bill_no'];
        $prodid = $row['p_id'];
        $pro = $row['p_id'];
        $amount = $row['price'];
        $q += $row['qty'];
        $pid = 'p ' . $row['p_id'];
        $tot += $row['total_price'];
        $d = $row['discount'];

        $result1 = mysqli_query($conn, "SELECT * FROM product_details WHERE pid = '$prodid'");
        if ($row1 = mysqli_fetch_array($result1)) {
            $pname = $row1['product_name'];
        }

        $sql = "UPDATE product_details SET quantity = quantity-'$qsingle' WHERE pid='$p'";
        if (mysqli_query($conn, $sql)) {
            if ($row1['quantity'] - $qsingle <= 5) {
                $lowStockProducts[] = [
                    'dealer' => $row1['dealer'],
                    'productName' => $pname,
                    'quantity' => $row1['quantity'] - $qsingle
                ];
            }
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    } else {
        $p = $row['p_id'];
        $qsingle = $row['qty'];
        $pro = $row['p_id'];
        $prodid .= ',' . $row['p_id'];
        $amount .= ',' . $row['price'];
        $q .= ',' . $row['qty'];
        $qt += $row['qty'];
        $pid = 'p ' . $row['p_id'];
        $tot += $row['total_price'];
        $d = $row['discount'];
        $result1 = mysqli_query($conn, "SELECT * FROM product_details WHERE pid = '$pro'");
        if ($row1 = mysqli_fetch_array($result1)) {
            $pname .= ',' . $row1['product_name'];
            $oq = $row1['quantity'] - $row['qty']; // quantity to be updated
        }
        $sql = "UPDATE product_details SET quantity = quantity-'$qsingle' WHERE pid='$p'";
        if (mysqli_query($conn, $sql)) {
            if ($oq <= 5) {
                $lowStockProducts[] = [
                    'dealer' => $row1['dealer'],
                    'productName' => $row1['product_name'],
                    'quantity' => $oq
                ];
            }
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    }
}

$fin = $tot - $tot * ($d / 100); // final amount
$f = round($fin);
$sql = "INSERT INTO bill_records (pid, total_amount, discout, qty, date, time, final_amount, product_name, product_price) VALUES ('$prodid', '$tot', '$d', '$q', '$dte', '$hourMin', '$f', '$pname', '$amount')";
if (!mysqli_query($conn, $sql)) {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}

$year = date("Y");
$sql1 = "INSERT INTO sales (month, year, time, date, quantity, amount, bill_no, day) VALUES ('$month', '$year', '$hourMin', '$dte', '$qt', '$f', '$billno', '$day')";
if (mysqli_query($conn, $sql1)) {
    $sql = "DELETE FROM bil_temp";
    if ($conn->query($sql) === TRUE) {
        include 'bpreview.html';
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    echo "Error: " . $sql1 . "<br>" . mysqli_error($conn);
}

sendLowStockEmail($conn, $lowStockProducts);

mysqli_close($conn);

function sendLowStockEmail($conn, $lowStockProducts) {
    $dealers = [];
    foreach ($lowStockProducts as $product) {
        $result = mysqli_query($conn, "SELECT additional_info FROM dealer_contact WHERE name = '{$product['dealer']}'");
        if ($row = mysqli_fetch_array($result)) {
            $dealers[$row['additional_info']][] = $product;
        }
    }

    foreach ($dealers as $dealerEmail => $products) {
        $subject = 'Low Stock Alert';
        $message = "Dear Dealer,<br><br>The following products are low in stock:<br><ul>";
        foreach ($products as $product) {
            $message .= "<li><strong>{$product['productName']}</strong>: {$product['quantity']} units remaining</li>";
        }
        $message .= "</ul><br>Please restock as soon as possible.<br><br>Thank you.";
        $headers = 'From: your_email@example.com' . "\r\n" .
                   'Reply-To: your_email@example.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion() . "\r\n" .
                   'Content-Type: text/html; charset=UTF-8';

        if (mail($dealerEmail, $subject, $message, $headers)) {
            echo 'Low stock email sent successfully.';
        } else {
            echo 'Error sending low stock email.';
        }
    }
}
?>
