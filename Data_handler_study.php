<?php
include "Account.php";

function saveStudyData(mysqli $conn, int $user_id, string $subject, string $projects, string $skills): bool
{
    $sql = "INSERT INTO sndata (user_id, subject, projects, skills) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed (saveStudyData): " . $conn->error);
        return false;
    }
    $stmt->bind_param("isss", $user_id, $subject, $projects, $skills);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Execute failed (saveStudyData): " . $stmt->error);
        $stmt->close();
        return false;
    }
}

function updateStudyData(mysqli $conn, int $id, int $user_id, string $subject, string $projects, string $skills): bool
{
    $sql = "UPDATE sndata SET subject = ?, projects = ?, skills = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed (updateStudyData): " . $conn->error);
        return false;
    }
    $stmt->bind_param("sssii", $subject, $projects, $skills, $id, $user_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return true;
        } else {
            error_log("No rows updated for sndata ID: $id, User ID: $user_id (updateStudyData)");
            $stmt->close();
            return false;
        }
    } else {
        error_log("Execute failed (updateStudyData): " . $stmt->error);
        $stmt->close();
        return false;
    }
}

function fetchStudyData(mysqli $conn, int $user_id): ?array
{
    $sql = "SELECT id, subject, projects, skills FROM sndata WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed (fetchStudyData): " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        } else {
            $stmt->close();
            return null;
        }
    } else {
        error_log("Execute failed (fetchStudyData): " . $stmt->error);
        $stmt->close();
        return null;
    }
}

?>