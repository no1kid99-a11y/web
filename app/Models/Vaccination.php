<?php
declare(strict_types=1);

namespace App\Models;

use Core\Database;

class Vaccination extends Model
{
    protected static string $table = 'vaccinations';

    public static function forPet(int $petId): array
    {
        return Database::fetchAll('SELECT * FROM vaccinations WHERE pet_id = ? ORDER BY vaccinated_at DESC', [$petId]);
    }
}
