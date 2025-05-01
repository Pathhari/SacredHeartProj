<?php

function checkAvailability($conn, $sacramentType, $preferredDate, $preferredTime) {
    // Query the UpcomingEvents table to check availability
    $query = "SELECT * FROM UpcomingEvents 
              WHERE EventDate = '$preferredDate' 
              AND StartTime <= '$preferredTime' 
              AND EndTime >= '$preferredTime'
              AND SacramentType = '$sacramentType'
              AND Status = 'Available'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function assignPriest($conn) {
    // Check for an available priest
    $query = "SELECT PriestID FROM Priests WHERE Availability = 'Available' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $priest = mysqli_fetch_assoc($result);
    if ($priest) {
        // Mark priest as unavailable after assignment
        $priestId = $priest['PriestID'];
        $updatePriest = "UPDATE Priests SET Availability = 'Unavailable' WHERE PriestID = '$priestId'";
        mysqli_query($conn, $updatePriest);
        return $priestId;
    }
    return null;
}

function scheduleSacrament($conn, $eventId, $refNo, $userId) {
    // Insert the scheduling into SacramentScheduling
    $query = "INSERT INTO SacramentScheduling (EventID, RefNo, UserID, Status) 
              VALUES ('$eventId', '$refNo', '$userId', 'Pending')";
    return mysqli_query($conn, $query);
}



?>