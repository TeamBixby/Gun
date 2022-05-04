<?php

declare(strict_types=1);

namespace TeamBixby\Gun\form;

use Closure;
use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\item\{VanillaItems, ItemFactory, Item, ItemIds};
use pocketmine\utils\Config;
use pocketmine\utils\Utils;
use pocketmine\nbt\tag\{ByteTag, CompoundTag, DoubleTag, FloatTag, StringTag, ListTag, ShortTag, IntTag};
use TeamBixby\Gun\Gun;
use TeamBixby\Gun\GunPlugin;

class GunListForm implements Form{
	/** @var Gun[] */
	protected array $guns = [];

	protected Closure $handleClosure;

	public function __construct(array $guns, Closure $handleClosure){
		$this->guns = $guns;
		Utils::validateCallableSignature(function(Player $player, int $data) : void{
		}, $handleClosure);
		$this->handleClosure = $handleClosure;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "form",
			"title" => "Gun",
			"content" => "",
			"buttons" => array_map(function(Gun $gun) : array{
				return ["text" => $gun->getName()];
			}, $this->guns)
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if(!is_int($data)){
			return;
		}
		($this->handleClosure)($player, $data);
	}
}