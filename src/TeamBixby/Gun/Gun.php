<?php

declare(strict_types=1);

namespace TeamBixby\Gun;

use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\particle\Particle;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\utils\Color;

use function count;

class Gun{
	/** @var string */
	protected string $name;
	/** @var Item */
	protected Item $item;
	/** @var float */
	protected float $damage;
	/** @var int */
	protected int $ammo = 0;
	/** @var int */
	protected int $scope = -1;
	/** @var int */
	protected int $reloadCooldown = 10;
	/** @var int */
	protected int $distance = 5;
	/** @var bool */
	protected bool $canPassWall = false;

	public function __construct(string $name, Item $item, float $damage, int $ammo, int $scope, int $reloadCooldown, int $distance, bool $canPassWall){
		$this->name = $name;
		$this->item = $item;
		$this->damage = $damage;
		$this->ammo = $ammo;
		$this->scope = $scope;
		$this->reloadCooldown = $reloadCooldown;
		$this->distance = $distance;
		$this->canPassWall = $canPassWall;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getItem() : Item{
		return clone $this->item;
	}

	public function getDamage() : float{
		return $this->damage;
	}

	public function getAmmo() : int{
		return $this->ammo;
	}

	public function getScope() : int{
		return $this->scope;
	}

	public function getReloadCooldown() : int{
		return $this->reloadCooldown;
	}

	public function getDistance() : int{
		return $this->distance;
	}

	public function canPassWall() : bool{
		return $this->canPassWall;
	}

	public function shoot(Player $player) : void{
		$pos = $player->getDirectionVector();

		$packets = [];


		for($i = 0; $i < $this->distance; $i++){
			$vec = $pos->multiply($i)->add($player)->add(0, 1.7);
			$block = $player->getLevel()->getBlock($vec);
			/*
			if(!$this->canPassWall && !$block->canPassThrough()){
				break;
			}*/
			$pk = new LevelEventPacket();
			$pk->evid = LevelEventPacket::EVENT_ADD_PARTICLE_MASK | (Particle::TYPE_SPARKLER & 0xFFF);
			$pk->data = (new Color(0, 255, 0))->toARGB();
			$pk->position = $vec;
			$packets[] = $pk;
			/** @var Living $nearEntity */
			$nearEntity = $player->getLevel()->getNearestEntity($vec, 2, Living::class);
			if($nearEntity !== null && $nearEntity !== $player){
				$nearEntity->attack(new EntityDamageByEntityEvent($player, $nearEntity, EntityDamageEvent::CAUSE_PROJECTILE, $this->damage));
				break;
			}
		}

		if(count($packets) > 0){
			$needAsync = count($packets) >= 15;
			$player->getServer()->batchPackets($player->getViewers() + [$player], $packets, !$needAsync);
		}
	}

	public function jsonSerialize() : array{
		return [
			"name" => $this->name,
			"item" => $this->item->jsonSerialize(),
			"damage" => $this->damage,
			"distance" => $this->distance,
			"scope" => $this->scope,
			"reloadCooldown" => $this->reloadCooldown,
			"ammo" => $this->ammo,
			"canPassWall" => $this->canPassWall
		];
	}
}