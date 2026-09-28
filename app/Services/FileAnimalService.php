<?php

namespace App\Services;

use App\Contracts\AnimalServiceInterface;
use App\Exceptions\AnimalNotFoundException;
use Illuminate\Support\Facades\Storage;

class FileAnimalService implements AnimalServiceInterface
{
    private string $file = 'animals.json';

    public function all(): array
    {
        return $this->getAnimals();
    }

    public function find(string $id): ?array
    {
        $animals = $this->getAnimals();

        if (!isset($animals[$id])) {
            throw AnimalNotFoundException::forId($id);
        }

        return $animals[$id];
    }

    public function create(array $data): array
    {
        $animals = $this->getAnimals();
        $newId = $this->nextId($animals);

        $animals[(string) $newId] = $data;
        $this->saveAnimals($animals);

        return ['id' => (string) $newId] + $data;
    }

    public function update(string $id, array $data): ?array
    {
        $animals = $this->getAnimals();

        if (!isset($animals[$id])) {
            throw AnimalNotFoundException::forId($id);
        }

        $animals[$id] = $data;
        $this->saveAnimals($animals);

        return ['id' => $id] + $data;
    }

    public function delete(string $id): bool
    {
        $animals = $this->getAnimals();

        if (!isset($animals[$id])) {
            throw AnimalNotFoundException::forId($id);
        }

        unset($animals[$id]);
        $this->saveAnimals($animals);

        return true;
    }

    public function reset(): void
    {
        $this->saveAnimals([]);
    }

    // Lee el JSON del disco y lo convierte a un arreglo id => datos
    private function getAnimals(): array
    {
        if (!Storage::disk('local')->exists($this->file)) {
            $this->saveAnimals([]);
            return [];
        }

        $json = Storage::disk('local')->get($this->file);
        $list = json_decode($json, true) ?? [];

        $animals = [];
        foreach ($list as $animal) {
            $id = (string) $animal['id'];
            unset($animal['id']);
            $animals[$id] = $animal;
        }

        return $animals;
    }

    // Convierte el arreglo id => datos a la lista con "id" que pide la consigna, y la guarda
    private function saveAnimals(array $animals): void
    {
        $list = [];
        foreach ($animals as $id => $data) {
            $list[] = ['id' => is_numeric($id) ? (int) $id : $id] + $data;
        }

        Storage::disk('local')->put(
            $this->file,
            json_encode($list, JSON_PRETTY_PRINT)
        );
    }

    // Calcula el próximo ID autoincremental
    private function nextId(array $animals): int
    {
        $ids = array_map('intval', array_keys($animals));
        return empty($ids) ? 1 : max($ids) + 1;
    }
}