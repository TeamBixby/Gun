<?php

declare(strict_types=1);

namespace TeamBixby\Gun\session;

use pocketmine\entity\Attribute;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\nbt\tag\{ByteTag, CompoundTag, DoubleTag, FloatTag, StringTag, ListTag, ShortTag, IntTag};
use pocketmine\utils\AssumptionFailedError;
use pocketmine\event\server\DataPacketReceiveEvent;
use TeamBixby\Gun\Gun;
use TeamBixby\Gun\GunPlugin;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;
use pocketmine\world\{Position, WorldManager, Location, World};
use function array_map;
use function implode;
use function str_replace;
use function time;

class Session{
	/**
	 * @var int[]
	 * name: count
	 */
	protected array $guns = [];
	/**
	 * @var int[]
	 * name: cool
	 */
	protected array $cools = [];
	/** @var Player */
	protected Player $player;
	/** @var Gun|null */
	protected ?Gun $gun = null;
	/** @var bool */
	protected bool $needBackMovement = false;
	/** @var float */
	protected float $movement = -1;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function addGun(Gun $gun) : void{
		$this->guns[$gun->getName()] = $gun->getAmmo();
		$this->cools[$gun->getName()] = -1;
	}

	public function useGun() : void{
		$gun = $this->getNowGun();
		if($gun === null){
			return;
		}
		if(!isset($this->guns[$gun->getName()]) || !isset($this->guns[$gun->getName()])){
			$this->addGun($gun);
		}

		if($this->guns[$gun->getName()] > 0){
			--$this->guns[$gun->getName()];
			$gun->shoot($this->player);
			return;
		}

		$this->reloadGun();

		if($this->cools[$gun->getName()] - time() > 0){
			return;
		}
		$this->unlockGun();
	}

	public function setNowGun(?Gun $gun = null) : void{
		$this->gun = $gun;
	}

	public function getNowGun() : ?Gun{
		return $this->gun;
	}

	public function sendInfo() : void{
		$this->prepare();
		$text = implode("\n", array_map(function(string $line) : string{
			return str_replace(["%gun%", "%ammo%"], [
				$this->gun->getName(),
				$this->guns[$this->gun->getName()] ?? $this->gun->getAmmo()
			], $line);
		}, GunPlugin::getInstance()->getConfig()->get("message.gunInfo")));

		if($this->guns[$this->gun->getName()] <= 0){
			$text .= "\n" . str_replace(["%cooldown%"], [$this->cools[$this->gun->getName()] - time()], GunPlugin::getInstance()->getConfig()->get("message.cooldown"));
		}

		$this->player->sendTip($text);
	}

	public function canReloadGun() : bool{
		$gun = $this->gun;
		if($gun === null){
			return false;
		}
		$this->prepare();

		return $this->guns[$gun->getName()] < 1;
	}

	public function reloadGun() : void{
		$gun = $this->gun;
		if($gun === null){
			return;
		}
		$this->prepare();

		if($this->cools[$gun->getName()] === -1){
			$this->cools[$gun->getName()] = time() + $gun->getReloadCooldown();
		}
	}

	public function unlockGun() : void{
		$gun = $this->gun;
		if($gun === null){
			return;
		}
		$this->prepare();

		$this->guns[$gun->getName()] = $gun->getAmmo();
		$this->cools[$gun->getName()] = -1;
	}

	public function isReloading() : bool{
		$gun = $this->gun;
		if($gun === null){
			return false;
		}
		$this->prepare();

		return $this->guns[$gun->getName()] < 1;
	}

	public function check() : void{
		$gun = $this->gun;
		if($gun === null){
			return;
		}
		$this->prepare();

		$this->sendInfo();

		if($this->canReloadGun()){
			$this->reloadGun();
		}

		if($this->isReloading()){
			if($this->cools[$gun->getName()] - time() > 0){
				return;
			}
			$this->unlockGun();
		}
	}

	public function prepare() : void{
		$gun = $this->gun;
		if($gun === null){
			return;
		}
		if(!isset($this->guns[$gun->getName()])){
			$this->guns[$gun->getName()] = $gun->getAmmo();
		}
		if(!isset($this->cools[$gun->getName()])){
			$this->cools[$gun->getName()] = -1;
		}
	}

	public function syncScope() : void{
		if($this->gun === null){
			if($this->needBackMovement && $this->movement !== -1){
				$this->player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->setValue((float) $this->movement);
				$this->needBackMovement = false;
				$this->movement = -1;
			}
			return;
		}
		if($this->gun->getScope() < 1){
			return;
		}
		if($this->needBackMovement === false && $this->movement === (float) -1){
			$this->needBackMovement = true;
			$this->movement = $this->player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->getValue();

			$calc = $this->calcMovementValue();
			if($calc <= $this->player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->getMinValue()){
				throw new AssumptionFailedError("Cannot set MOVEMENT_SPEED smaller than min value");
			}
			$this->player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->setValue($calc);
		}
	}

	public function calcMovementValue() : float{
		$movementValue = $this->player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->getValue();

		$scope = $this->gun->getScope();

		return $movementValue * (1 - 0.15 * $scope);
	}
}