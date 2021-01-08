<?php

declare(strict_types=1);

namespace TeamBixby\Gun\form;

use pocketmine\form\Form;
use pocketmine\Player;
use TeamBixby\Gun\GunPlugin;

class GunMainForm implements Form{

	public function jsonSerialize() : array{
		return [
			"type" => "form",
			"title" => "Gun main menu",
			"content" => "",
			"buttons" => [
				["text" => "Exit"],
				["text" => "Create gun"],
				["text" => "Get gun"],
				["text" => "Remove gun"]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if(!is_int($data)){
			return;
		}

		switch($data){
			case 1:
				$player->sendForm(new GunCreateForm());
				break;
			case 2:
				$player->sendForm(new GunListForm($guns = GunPlugin::getInstance()->getGuns(), function(Player $player, int $data) use ($guns) : void{
					if(isset($guns[$data])){
						$player->getInventory()->addItem(GunPlugin::getInstance()->designGun($guns[$data]));
					}
				}));
				break;
			case 3:
				$player->sendForm(new GunListForm($guns = GunPlugin::getInstance()->getGuns(), function(Player $player, int $data) use ($guns) : void{
					if(isset($guns[$data])){
						GunPlugin::getInstance()->unregisterGun($guns[$data]);
					}
				}));
				break;
		}
	}
}