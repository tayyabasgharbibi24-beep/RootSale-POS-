<?php
// includes/auth.php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isSeller() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller';
}

function isInventoryManager() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'inventory';
}

function isCustomerDisplay() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer_display';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "/dashboard.php?error=unauthorized");
        exit;
    }
}

function requireSellerOrAdmin() {
    requireLogin();
    if (!isAdmin() && !isSeller()) {
        header("Location: " . BASE_URL . "/dashboard.php?error=unauthorized");
        exit;
    }
}

function requireInventoryManagerOrAdmin() {
    requireLogin();
    if (!isAdmin() && !isInventoryManager()) {
        header("Location: " . BASE_URL . "/dashboard.php?error=unauthorized");
        exit;
    }
}
