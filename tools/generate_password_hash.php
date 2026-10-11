<?php
/**
 * Local helper only - do NOT upload this file to InfinityFree.
 *
 * Run it on your own machine (or anywhere with a PHP CLI) to generate a
 * password hash to paste into the `users` table:
 *
 *   php generate_password_hash.php "MySecretPassword123"
 *
 * It prints a bcrypt hash compatible with password_verify(), which is
 * what login.php checks against. Do the same for flags: the value you
 * hash for a challenge's flag_hash column is the exact flag string
 * players must type in (e.g. AVALON{example_flag_1}).
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php generate_password_hash.php <plaintext>\n");
    exit(1);
}

echo password_hash($argv[1], PASSWORD_DEFAULT) . "\n";
