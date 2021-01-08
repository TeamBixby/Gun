<?php

declare(strict_types=1);

namespace TeamBixby\Gun\form;

use Closure;
use pocketmine\form\Form;
use pocketmine\Player;
use pocketmine\utils\Utils;
use TeamBixby\Gun\Gun;

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