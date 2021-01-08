<?php

declare(strict_types=1);

namespace TeamBixby\Gun\command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use TeamBixby\Gun\form\GunMainForm;
use TeamBixby\Gun\GunPlugin;

class GunCommand extends PluginCommand{

	public function __construct(){
		parent::__construct("gun", GunPlugin::getInstance());
		$this->setDescription("Manage the gun");
		$this->setPermission("gun.command");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!$sender instanceof Player){
			return false;
		}
		$sender->sendForm(new GunMainForm());
		return true;
	}
}