<?php
namespace App\Services;

class PasswordGenerator
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const NUMBERS   = '0123456789';
    private const SPECIAL   = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    /**
     * Generate a password with explicit character counts.
     *
     * @param int $uppercase Number of uppercase letters.
     * @param int $lowercase Number of lowercase letters.
     * @param int $numbers   Number of digit characters.
     * @param int $special   Number of special characters.
     * @return string The generated password.
     * @throws \InvalidArgumentException if total length is zero.
     */
    public function generate(
        int $uppercase = 2,
        int $lowercase = 3,
        int $numbers   = 2,
        int $special   = 2
    ): string {
        $total = $uppercase + $lowercase + $numbers + $special;
        if ($total === 0) {
            throw new \InvalidArgumentException('Total password length must be at least 1.');
        }

        $chars = [];

        $chars = array_merge(
            $chars,
            $this->pickRandom(self::UPPERCASE, $uppercase),
            $this->pickRandom(self::LOWERCASE, $lowercase),
            $this->pickRandom(self::NUMBERS,   $numbers),
            $this->pickRandom(self::SPECIAL,   $special)
        );

        // Cryptographically shuffle
        $this->shuffle($chars);

        return implode('', $chars);
    }

    /**
     * Factory: build a password from percentages of a desired total length.
     *
     * Percentages do not need to sum to 100; they are scaled proportionally.
     * Any remainder characters are filled as lowercase.
     *
     * @param int   $totalLength  Desired total password length.
     * @param float $uppercasePct Percentage of uppercase letters (0-100).
     * @param float $lowercasePct Percentage of lowercase letters (0-100).
     * @param float $numbersPct   Percentage of digits (0-100).
     * @param float $specialPct   Percentage of special chars (0-100).
     */
    public function fromPercents(
        int   $totalLength  = 12,
        float $uppercasePct = 25.0,
        float $lowercasePct = 25.0,
        float $numbersPct   = 25.0,
        float $specialPct   = 25.0
    ): string {
        $uppercase = (int) round($totalLength * $uppercasePct / 100);
        $numbers   = (int) round($totalLength * $numbersPct   / 100);
        $special   = (int) round($totalLength * $specialPct   / 100);
        // Give remainder to lowercase so total stays exact
        $lowercase = max(0, $totalLength - $uppercase - $numbers - $special);

        return $this->generate($uppercase, $lowercase, $numbers, $special);
    }

    //Helpers

    /**
     * Pick $count random characters from $alphabet using random_int.
     */
    private function pickRandom(string $alphabet, int $count): array
    {
        $result = [];
        $max    = strlen($alphabet) - 1;
        for ($i = 0; $i < $count; $i++) {
            $result[] = $alphabet[random_int(0, $max)];
        }
        return $result;
    }

    /**
     * Fisher-Yates shuffle using random_int (cryptographically secure).
     */
    private function shuffle(array &$array): void
    {
        for ($i = count($array) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }
    }
}
