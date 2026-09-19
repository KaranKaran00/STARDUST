<?php
/**
 * Auth helpers. Two independent session identities can exist:
 *   $_SESSION['founder']  = ['id'=>, 'full_name'=>, 'email'=>]
 *   $_SESSION['investor'] = ['id'=>, 'full_name'=>, 'email'=>, 'firm_name'=>]
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function current_founder(): ?array
{
    return $_SESSION['founder'] ?? null;
}

function current_investor(): ?array
{
    return $_SESSION['investor'] ?? null;
}

function require_founder(): array
{
    $f = current_founder();
    if (!$f) {
        flash('error', 'Please log in to continue.');
        redirect('/login_founder.php');
    }
    return $f;
}

function require_investor(): array
{
    $i = current_investor();
    if (!$i) {
        flash('error', 'Please log in to continue.');
        redirect('/login_investor.php');
    }
    return $i;
}

function login_founder(array $row): void
{
    session_regenerate_id(true);
    $_SESSION['founder'] = [
        'id' => $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
    ];
}

function login_investor(array $row): void
{
    session_regenerate_id(true);
    $_SESSION['investor'] = [
        'id' => $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'firm_name' => $row['firm_name'],
    ];
}
