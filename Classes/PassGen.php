<?php
namespace App\Services;
 
class PassGen
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const NUMBERS   = '0123456789';
    private const SPECIAL   = '!@#$%^&*()_+-=[]{}|;:,.<>?';
 
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
 
        $chars = array_merge(
            $this->pickRandom(self::UPPERCASE, $uppercase),
            $this->pickRandom(self::LOWERCASE, $lowercase),
            $this->pickRandom(self::NUMBERS,   $numbers),
            $this->pickRandom(self::SPECIAL,   $special)
        );
 
        $this->shuffle($chars);
        return implode('', $chars);
    }
 
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
        $lowercase = max(0, $totalLength - $uppercase - $numbers - $special);
 
        return $this->generate($uppercase, $lowercase, $numbers, $special);
    }
 
    private function pickRandom(string $alphabet, int $count): array
    {
        $result = [];
        $max    = strlen($alphabet) - 1;
        for ($i = 0; $i < $count; $i++) {
            $result[] = $alphabet[random_int(0, $max)];
        }
        return $result;
    }
 
    private function shuffle(array &$array): void
    {
        for ($i = count($array) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }
    }
}