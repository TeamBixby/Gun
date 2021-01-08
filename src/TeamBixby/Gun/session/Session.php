<?php

declare(strict_types=1);

namespace TeamBixby\Gun\session;

use pocketmine\Player;
use TeamBixby\Gun\Gun;

use TeamBixby\Gun\GunPlugin;

use function array_map;
use function implode;
use function str_replace;

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

		if($this->guns[$gun->getName()] !== 0){
			--$this->guns[$gun->getName()];
			$gun->shoot($this->player);
			return;
		}

		$this->reloadGun();

		if(time() - $this->cools[$gun->getName()] < $gun->getReloadCooldown()){
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
			return str_replace(["%gun%", "%ammo%"], [$this->gun->getName(), $this->guns[$this->gun->getName()] ?? $this->gun->getAmmo()], $line);
		}, GunPlugin::getInstance()->getConfig()->get("message.gunInfo")));

		if($this->guns[$this->gun->getName()] <= 0){
			$text .= str_replace(["%cooldown%"], [$this->gun->getReloadCooldown()], GunPlugin::getInstance()->getConfig()->get("message.cooldown"));
		}

		$this->player->sendTip($text);
	}

	public function canReloadGun() : bool{
		$gun = $this->gun;
		if($gun === null){
			return false;
		}
		$this->prepare();

		return $this->guns[$gun->getName()] <= 0;
	}

	public function reloadGun() : void{
		$gun = $this->gun;
		if($gun === null){
			return;
		}
		$this->prepare();

		if($this->cools[$gun->getName()] === -1){
			$this->cools[$gun->getName()] = time();
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

		return $this->cools[$gun->getName()] !== -1;
	}

	public function check() : void{
		$gun = $this->gun;
		if($gun === null){
			return;
		}
		$this->prepare();

		if($this->canReloadGun()){
			$this->reloadGun();
		}

		if($this->isReloading()){
			if(time() - $this->cools[$gun->getName()] < $gun->getReloadCooldown()){
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
}