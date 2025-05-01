<?php
// Start session
session_start();

// Include database connection file
include 'db.php';

// Check if 'ref' parameter is set
if (isset($_GET['ref'])) {
    $refNo = $_GET['ref'];

    // Fetch the baptism request details from the database
    $sql = "SELECT * FROM BaptismRequest WHERE RefNo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $refNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();

        // Fetch godparents
        $requesterID = $request['RequesterID'];
        $sqlGodparents = "SELECT * FROM Godparents WHERE RequesterID = ?";
        $stmtGodparents = $conn->prepare($sqlGodparents);
        $stmtGodparents->bind_param('i', $requesterID);
        $stmtGodparents->execute();
        $resultGodparents = $stmtGodparents->get_result();
        $godparents = [];
        while ($gp = $resultGodparents->fetch_assoc()) {
            $godparents[] = $gp;
        }

        // Separate godparents into godfathers and godmothers
        $godfathers = [];
        $godmothers = [];
        foreach ($godparents as $gp) {
            if ($gp['GodparentType'] === 'Godfather') {
                $godfathers[] = $gp;
            } elseif ($gp['GodparentType'] === 'Godmother') {
                $godmothers[] = $gp; // Corrected line
            }
        }

        // Prepare arrays to hold the names and residences
        $ninongNames = [];
        $ninongResidences = [];
        $ninangNames = [];
        $ninangResidences = [];

        // Assuming there are up to 4 godfathers and godmothers
        for ($i = 0; $i < 4; $i++) {
            if (isset($godfathers[$i])) {
                $ninongNames[$i] = $godfathers[$i]['GodparentName'];
                $ninongResidences[$i] = $godfathers[$i]['GodparentAddress'];
            } else {
                $ninongNames[$i] = '';
                $ninongResidences[$i] = '';
            }

            if (isset($godmothers[$i])) {
                $ninangNames[$i] = $godmothers[$i]['GodparentName'];
                $ninangResidences[$i] = $godmothers[$i]['GodparentAddress'];
            } else {
                $ninangNames[$i] = '';
                $ninangResidences[$i] = '';
            }
        }
    } else {
        echo "No request found with the provided reference number.";
        exit;
    }
} else {
    echo "No reference number provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form sa Magpabunyag</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }
        .form-container {
            width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #000;
        }
        .form-header {
            text-align: left;
            display: flex;
            justify-content: space-between;
        }
        .form-header-left,
        .form-header-right {
            width: 48%;
        }
        .form-header img {
            width: 100px; /* Adjust the size of the logo as needed */
            height: auto;
        }
        .header-text {
            text-align: right;
            width: 70%;
        }
        .header-text h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header-text h2, .header-text h3, .header-text h4 {
            margin: 0;
            font-size: 16px;
        }
        .gkk-section {
            display: block;
        }
        .right-fields {
            text-align: right;
            margin-top: -40px;
        }
        .right-fields span {
            display: block;
        }
        .right-fields .gender-section {
            margin-top: 10px;
        }
        .form-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .form-table td {
            padding: 5px;
            border: 1px solid black;
            vertical-align: middle;
        }
        .form-footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .form-footer .half-width {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        .form-footer input[type="text"] {
            width: 100%;
            border: none;
            border-bottom: 1px solid black;
            padding: 5px;
            margin-bottom: 5px;
        }
        .left-side {
            width: 70%;
            line-height: 2;
        }
        .right-side {
            width: 30%;
            text-align: right;
            line-height: 2;
        }
        .signatures {
            text-align: center;
            margin-top: 30px;
        }
        .signatures .half-width {
            width: 45%;
            display: inline-block;
            margin: 0 2%;
            text-align: center;
        }
        .signatures h4 {
            margin-bottom: 60px;
            border-top: 1px solid black;
            padding-top: 10px;
        }
        /* Ninong/Ninang section */
        .ninong-ninang {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .ninong-ninang div {
            width: 22%;
            text-align: center;
        }
        .ninong-ninang h4 {
            margin-bottom: 10px;
        }
        .ninong-ninang input {
            width: 100%;
            border: none;
            border-bottom: 1px solid black;
            text-align: center;
            margin-bottom: 10px;
        }
        .requirements {
            margin-top: 30px;
        }
        .requirements h4 {
            margin-bottom: 10px;
        }
        .requirements ul {
            list-style: none;
            padding-left: 0;
        }
        .fees {
            margin-top: 20px;
        }
        .fees table {
            width: 100%;
            border-collapse: collapse;
        }
        .fees table, .fees th, .fees td {
            border: 1px solid black;
            padding: 5px;
            text-align: center;
        }
        .print-button {
            margin: 20px 0;
            text-align: center;
        }
        .print-button button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .print-button button:hover {
            background-color: #45a049;
        }
    </style>

<div class="form-container">
    <div class="form-header">
        <img src="imgs/mainlogo.png" alt="Sacred Heart Logo">
        <div class="header-text">
            <h1>Sacred Heart of Jesus Parish</h1>
            <h2>Diocese of Mati</h2>
            <h3>Don Bosco Compound</h3>
            <h4>Dahican, City of Mati, Davao Oriental</h4>
        </div>
    </div>
    <br>

    <div class="gkk-section">
        <span>GKK: <?php echo htmlspecialchars($request['GKK_BEC']); ?></span> <br> <br>
        <span>Birth Certificate No.: <?php echo htmlspecialchars($request['BirthCertNo']); ?></span>
    </div>

    <div class="right-fields">
        <span>Petsa sa Pagbunyag: <?php echo htmlspecialchars($request['BaptismalDate']); ?></span>
        <div class="gender-section">
            <span>Gender: <?php echo htmlspecialchars($request['Gender']); ?></span>
        </div>
    </div>

    <table class="form-table">
        <tr>
            <td>Pangalan sa Bata:</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['ChildName']); ?>"></td>
        </tr>
        <tr>
            <td>Petsa sa Pagkatawo sa Bata:</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['ChildDOB']); ?>"></td>
        </tr>
        <tr>
            <td>Dapit na Natawhan sa Bata:</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['ChildBPlace']); ?>"></td>
        </tr>
        <tr>
            <td>Pangalan sa Amahan:</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['FatherName']); ?>"></td>
        </tr>
        <tr>
            <td>Dapit nga Natawhan sa Amahan:</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['FatherBPlace']); ?>"></td>
        </tr>
        <tr>
            <td>Pangalan sa Inahan (Apelyedo sa dalaga):</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['MotherMName']); ?>"></td>
        </tr>
        <tr>
            <td>Dapit nga Natawhan sa Inahan:</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['MotherBPlace']); ?>"></td>
        </tr>
        <tr>
            <td>Pinuy-anan sa Ginikanan:</td>
            <td><input type="text" style="width: 100%; border: none;" value="<?php echo htmlspecialchars($request['ParentsResidence']); ?>"></td>
        </tr>
    </table>

    <div class="form-footer">
        <div class="left-side">
            <span>Petsa sa pagkasal sa pari: <?php echo htmlspecialchars($request['DMarriage']); ?></span>
            <br>
            <span>Simbahan nga gikaslan: <?php echo htmlspecialchars($request['PMarriage']); ?></span>
            <br>
            <span>Dapit: <?php echo htmlspecialchars($request['MarriagePlace']); ?></span>
        </div>
        <div class="right-side">
            <span>Marriage Cert. No.: <?php echo htmlspecialchars($request['MCertNo']); ?></span>
        </div>
    </div>

    <div class="signatures">
        <div class="half-width">
            <h4>Pirma sa Inahan</h4>
        </div>
        <div class="half-width">
            <h4>Pirma sa Amahan</h4>
        </div>
    </div>

    <!-- Ninong, Ninang, Pinuy-anan -->
    <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="ninong-ninang">
        <div>
            <h4>Ninong <?php echo $i + 1; ?></h4>
            <input type="text" name="ninong-name-<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($ninongNames[$i]); ?>">
        </div>
        <div>
            <h4>Pinuy-anan <?php echo $i + 1; ?></h4>
            <input type="text" name="ninong-residence-<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($ninongResidences[$i]); ?>">
        </div>
        <div>
            <h4>Ninang <?php echo $i + 1; ?></h4>
            <input type="text" name="ninang-name-<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($ninangNames[$i]); ?>">
        </div>
        <div>
            <h4>Pinuy-anan <?php echo $i + 1; ?></h4>
            <input type="text" name="ninang-residence-<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($ninangResidences[$i]); ?>">
        </div>
    </div>
    <?php endfor; ?>

    <div class="signatures" style="margin-top: 20px;">
        <div class="half-width">
            <h4>Pirma sa Pari nga Nagbunyag</h4>
        </div>
        <div class="half-width">
            <h4>Pirma sa FLAW</h4>
        </div>
    </div>

    <!-- Requirements Section -->
    <div class="requirements">
        <h4>Mga Gikinahanglan:</h4>
        <ul>
            <li>1. Birth Certificate sa bata</li>
            <li>2. Marriage Certificate sa Simbahan</li>
            <li>3. GKK or Parish Certificate sa Ginikanan ug mga Mangugos</li>
            <li>4. Kandila</li>
        </ul>
    </div>

    <!-- Fees Section -->
    <div class="fees">
        <h4>Halad sa Bunyag:</h4>
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Aktibo</th>
                    <th>Dili Aktibo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Bata</td>
                    <td>₱320.00</td>
                    <td>₱420.00</td>
                </tr>
                <tr>
                    <td>Hamting</td>
                    <td>₱250.00</td>
                    <td>₱430.00</td>
                </tr>
                <tr>
                    <td>Mangugos</td>
                    <td>₱50.00</td>
                    <td>₱75.00</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="print-button">
    <button onclick="printPage()">Print this page</button>
</div>

<script>
    function printPage() {
        const printStyle = document.getElementById("printStyle");

        if (!printStyle) {
            const style = document.createElement("style");
            style.id = "printStyle";
            document.head.appendChild(style);
        }

        const css = `
            @media print {
                @page {
                    size: A4;
                    margin: 5mm;
                }
                body {
                    zoom: 0.75; /* Adjusted to fit on one page */
                }
                .form-container {
                    width: 100%;
                    padding: 10px;
                    margin: 0;
                    box-sizing: border-box;
                    border: none; /* Remove the container border */
                }
                .form-header img {
                    max-width: 60px;
                }
                .form-header h1, .form-header h2, .form-header h3, .form-header h4 {
                    font-size: 14px;
                    margin: 0;
                }

                /* Apply borders to each table cell */
                .form-table td {
                    padding: 4px;
                    font-size: 12px;
                    border: 1px solid black; /* Border around each cell */
                }

                /* Make the field names (first column) bold */
                .form-table td:first-child {
                    font-weight: bold; /* Bold for the first column */
                }

                /* Hide the print button when printing */
                .print-button {
                    display: none; /* Hide the print button */
                }

                .signatures h4 {
                    font-size: 12px;
                }
                .ninong-ninang input {
                    font-size: 12px;
                }
                .fees table, .fees td, .fees th {
                    font-size: 12px;
                    border: 1px solid black; /* Border for the fees table */
                }
                .requirements h4 {
                    font-size: 14px;
                }
            }

            /* Default view for the screen */
            .form-container {
                border: none; /* Remove the container border */
            }
            .form-table td, .fees td, .fees th {
                border: 1px solid black; /* Add table borders */
            }

            /* Make the field names (first column) bold for screen view */
            .form-table td:first-child {
                font-weight: bold; /* Bold for the first column */
            }
        `;

        document.getElementById("printStyle").innerHTML = css;

        window.print();
    }
</script>

</body>
</html>