<?php

declare(strict_types=1);

namespace TeamBixby\Gun\form;

use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\plugin\{Plugin, PluginBase};
use pocketmine\Server;
use pocketmine\item\{VanillaItems, ItemFactory, Item, ItemIds};
use pocketmine\utils\Config;
use pocketmine\utils\Utils;
use pocketmine\nbt\tag\{ByteTag, CompoundTag, DoubleTag, FloatTag, StringTag, ListTag, ShortTag, IntTag};
use TeamBixby\Gun\Gun;
use TeamBixby\Gun\GunPlugin;

use function count;
use function is_array;
use function is_numeric;

class GunCreateForm implements Form{

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "Create gun",
			"content" => [
				[
					"type" => "input",
					"text" => "Name of gun"
				],
				[
					"type" => "input",
					"text" => "Gun reload cooldown"
				],
				[
					"type" => "input",
					"text" => "Damage of gun"
				],
				[
					"type" => "input",
					"text" => "Distance of gun"
				],
				[
					"type" => "toggle",
					"text" => "Can pass wall",
					"default" => false
				],
				[
					"type" => "input",
					"text" => "Ammo of gun"
				],
				[
					"type" => "input",
					"text" => "Scope of gun, -1 is disable"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if(!is_array($data) || count($data) !== 7){
			return;
		}
		[$name, $cooldown, $damage, $distance, $passWall, $ammo, $scope] = $data;
		if(!isset($name) || $this->getPlugin()->getGun($name) !== null){
			return;
		}
		if(!isset($cooldown) || !is_numeric($cooldown) || ($cooldown = (int) $cooldown) < 0){
			return;
		}
		if(!isset($damage) || !is_numeric($damage) || ($damage = (int) $damage) < 1){
			return;
		}
		if(!isset($distance) || !is_numeric($distance) || ($distance = (int) $distance) < 1){
			return;
		}
		if(!isset($ammo) || !is_numeric($ammo) || ($ammo = (int) $ammo) < 1){
			return;
		}
		if(!isset($scope) || !is_numeric($scope) || ($scope = (int) $scope) < -2){
			return;
		}
		$item = $player->getInventory()->getItemInHand();
		if($item->isNull()){
			return;
		}
		$gun = new Gun($name, $item, $damage, $ammo, $scope, $cooldown, $distance, $passWall);
		$this->getPlugin()->registerGun($gun);
		$player->getInventory()->setItemInHand($this->getPlugin()->designGun($gun));
		$player->sendMessage("Gun registered successfully.");
	}
}