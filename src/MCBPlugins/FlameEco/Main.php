<?php
namespace MCBPlugins\FlameEco;
    
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener
 {
    
    public $db;
    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->db = new \SQLite3($this->getDataFolder() . "FlameEco.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS currency(currency TEXT PRIMARY KEY, account TEXT, money INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS symbol(currency TEXT PRIMARY KEY, symbol TEXT);");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function getCurrency($currency) {
		$type = \SQLite3::escapeString($currency);
		$check = $this->db->query("SELECT currency FROM currency WHERE currency='$type';");
		$send = $check->fetchArray(SQLITE3_ASSOC);
		return empty($send) == false;
    }
    public function newCurrency($currency) {
        $table = $this->db->prepare("INSERT OR REPLACE INTO currency(currency) VALUES (:currency);");
        $table->bindValue(":currency", $currency);
        $run = $table->execute();
    }
    public function delCurrency($currency) {
        $this->db->query("DELETE FROM currency WHERE currency='$currency';");
        $this->db->query("DELETE FROM symbol WHERE currency='$currency';");
    }
    public function newAccount($currency,$account) {
        $table = $this->db->prepare("INSERT OR REPLACE INTO currency (currency, account) VALUES (:currency, :account);");
        $table->bindValue(":currency", $currency);
        $table->bindValue(":account", $account);
        $run = $table->execute();
    }
    public function getPlayerMoney($currency,$account) {
        $select = $this->db->query("SELECT money FROM currency WHERE currency = '$currency' AND account = '$account';");
        $da = $select->fetchArray(SQLITE3_ASSOC);
        return (int) $da["money"];
    }
    public function getSymbol($currency) {
        $select = $this->db->query("SELECT symbol FROM symbol WHERE currency = '$currency';");
        $da = $select->fetchArray(SQLITE3_ASSOC);
        return $da["symbol"];
    }
    public function setSymbol($currency, $symbol) {
        $add = $this->db->prepare("INSERT OR REPLACE INTO symbol(currency, symbol) VALUES (:currency, :symbol);");
        $add->bindValue(":currency", $currency);
        $add->bindValue(":symbol", $symbol);
        $add->execute();
    }
    public function addMoney($currency,$account,$budget) {
        $add = $this->db->prepare("INSERT OR REPLACE INTO currency(currency, account, money) VALUES (:currency, :account, :money);");
        $add->bindValue(":currency", $currency);
        $add->bindValue(":account", $account);
        $add->bindValue(":money", $this->getPlayerMoney($currency, $account) + $budget);
        $add->execute();
    }
    public function takeMoney($currency,$account,$budget) {
        $add = $this->db->prepare("INSERT OR REPLACE INTO currency(currency, account, money) VALUES (:currency, :account, :money);");
        $add->bindValue(":currency", $currency);
        $add->bindValue(":account", $account);
        $add->bindValue(":money", $this->getPlayerMoney($currency, $account) - $budget);
        $add->execute();
    }
    public function setMoney($currency,$account,$budget) {
        $add = $this->db->prepare("INSERT OR REPLACE INTO currency (currency, account, money) VALUES (:currency, :account, :money);");
        $add->bindValue(":currency", $currency);
        $add->bindValue(":account", $account);
        $add->bindValue(":money", $budget);
        $add->execute();
    }
    public function payMoney($currency,$account,$payto,$budget) {
        $this->takeMoney($currency,$account,$budget);
        $this->addMoney($currency,$payto,$budget);
    }
    public function getAccount($currency,$account) {
    $type = \SQLite3::escapeString($currency);
    $client = \SQLite3::escapeString($account);
    $answer = $this->db->query("SELECT account FROM currency WHERE currency='$type' AND account='$client';");
    $last = $answer->fetchArray(SQLITE3_ASSOC);
    return empty($last) == false;
}
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) == "ecocreate") {
            if ($sender->hasPermission("eco.admin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($args[1])) {
                        $currency = $args[0];
                        $symbol = $args[1];
                        if (!$this->getCurrency($args[0])) {
                            $this->newCurrency($currency);
                            $this->setSymbol($currency, $symbol);
                            $sender->sendMessage(TextFormat::YELLOW . "You have created an economy currency called $currency!\nYou have created a symbol $symbol");
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Currency already exists!");
                        }
                        } else {
                        $sender->sendMessage(TextFormat::RED . "Please set the symbol!");
                    }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please set a currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.currency!");
                return false;
            }
        }
        
        if (strtolower($command->getName()) == "currencysymbol") {
            if ($sender->hasPermission("eco.admin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($args[1])) {
                        $currency = $args[0];
                        if ($this->getCurrency($args[0])) {
                            $symbol = $args[1];
                            $this->setSymbol($currency, $symbol);
                            $sender->sendMessage(TextFormat::YELLOW . "$currency symbol updated to $symbol!\nThis can be updated at any time.");
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Currency doesn't exists!");
                        }
                        } else {
                        $sender->sendMessage(TextFormat::RED . "Please set a symbol!");
                    }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please set a currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.currency!");
                return false;
            }
        }

        if (strtolower($command->getName()) == "ecodel") {
            if ($sender->hasPermission("eco.admin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        $currency = $args[0];
                        if ($this->getCurrency($args[0])) {
                            $this->delCurrency($currency);
                            $sender->sendMessage(TextFormat::YELLOW . "You have deleted an economy currency called $currency!");
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Currency doesn't exists!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose a currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.currency!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "ecocreateaccount") {
            if ($sender->hasPermission("eco.default")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        $account = $sender->getName();
                        $currency = $args[0];
                        if ($this->getCurrency($currency)) {
                            if (!$this->getAccount($currency, $account)) {
                                $this->newAccount($currency, $account);
                                $sender->sendMessage(TextFormat::YELLOW . "You have created an account $account!");
                                return true;
                            } else {
                                $sender->sendMessage(TextFormat::RED . "Account Exists!");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Currency doesn't exist!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose a currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.default!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "addmoney") {
            if ($sender->hasPermission("eco.admin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($args[1])) {
                            if (isset($args[2])) {
                                $account = $args[1];
                                $currency = $args[0];
                                if ($this->getCurrency($currency)) {
                                    if (is_numeric($args[2])) {
                                    if ($this->getAccount($currency, $account)) {
                                        $budget = $args[2];
                                        $this->addMoney($currency, $account, $budget);
                                        $symbol = $this->getSymbol($currency);
                                        $sender->sendMessage(TextFormat::YELLOW . "You have added $symbol$budget to $account's account!");
                                        return true;
                                    } else {
                                        $sender->sendMessage(TextFormat::RED . "Account doesn't exist!");
                                    }
                                    } else {
                                    $sender->sendMessage(TextFormat::RED . "Set a number!");
                                }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Currency doesn't exist!");
                                }
                            } else {
                                $sender->sendMessage(TextFormat::RED . "Please choose money to add!");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Please choose an account to add to!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose a currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.currency!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "setmoney") {
            if ($sender->hasPermission("eco.admin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($args[1])) {
                            if (isset($args[2])) {
                                $account = $args[2];
                                $currency = $args[0];
                                if ($this->getCurrency($currency)) {
                                    if (is_numeric($args[1])) {
                                    if ($this->getAccount($currency, $account)) {
                                        $budget = $args[1];
                                        $this->setMoney($currency, $account, $budget);
                                        $symbol = $this->getSymbol($currency);
                                        $sender->sendMessage(TextFormat::YELLOW . "You have set $account's blance to $symbol$budget!");
                                        return true;
                                    } else {
                                        $sender->sendMessage(TextFormat::RED . "Account doesn't exist!");
                                    }
                                    } else {
                                    $sender->sendMessage(TextFormat::RED . "Set a number!");
                                }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Currency doesn't exist!");
                                }
                            } else {
                                $sender->sendMessage(TextFormat::RED . "Please choose account to set!");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Please choose money to set!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose a currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.currency!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "takemoney") {
            if ($sender->hasPermission("eco.admin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($args[1])) {
                            if (isset($args[2])) {
                                $account = $args[2];
                                $currency = $args[0];
                                if ($this->getCurrency($currency)) {
                                    if (is_numeric($args[1])) {
                                    if ($this->getAccount($currency, $account)) {
                                        $budget = $args[1];
                                        $this->takeMoney($currency, $account, $budget);
                                        $symbol = $this->getSymbol($currency);
                                        $sender->sendMessage(TextFormat::YELLOW . "You have taken $symbol$budget from $account's account!");
                                        return true;
                                    } else {
                                        $sender->sendMessage(TextFormat::RED . "Account doesn't exist!");
                                    }
                                    } else {
                                    $sender->sendMessage(TextFormat::RED . "Set a number!");
                                }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Currency doesn't exist!");
                                }
                            } else {
                                $sender->sendMessage(TextFormat::RED . "Please choose account to take!");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Please choose money to take!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose a currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.currency!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "mymoney") {
            if ($sender->hasPermission("eco.default")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        $account = $sender->getName();
                        $currency = $args[0];
                        if ($this->getCurrency($currency)) {
                            if ($this->getAccount($currency, $account)) {
                                $stored = $this->getPlayerMoney($currency, $account);
                                $symbol = $this->getSymbol($currency);
                                $sender->sendMessage(TextFormat::YELLOW . "You have $symbol$stored in your account!");
                                return true;
                            } else {
                                $sender->sendMessage(TextFormat::RED . "Account doesn't exist!");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Currency doesn't exists!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.default!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "seemoney") {
            if ($sender->hasPermission("eco.default")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($args[1])) {
                            $account = $args[1];
                            $currency = $args[0];
                            if ($this->getCurrency($currency)) {
                                if ($this->getAccount($currency, $account)) {
                                    $budget = $args[1];
                                    $stored = $this->getPlayerMoney($currency, $account);
                                    $symbol = $this->getSymbol($currency);
                                    $sender->sendMessage(TextFormat::YELLOW . "They have $symbol$stored in their account!");
                                    return true;
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Account doesn't exist!");
                                }
                            } else {
                                $sender->sendMessage(TextFormat::RED . "Currency doesn't exist!");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Please choose an account!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.default!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "topmoney") {
            if ($sender->hasPermission("eco.default")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        $currency = $args[0];
                        if ($this->getCurrency($currency)) {
                            $load = $this->db->query("SELECT account FROM currency WHERE currency='$currency' ORDER BY money DESC LIMIT 10;");
                            $number = 0;
                            $sender->sendMessage(TextFormat::BLUE . "--Richest 10 accounts--");
                            while ($check = $load->fetchArray(SQLITE3_ASSOC)) {
                                $rank = $number + 1;
                                $account = $check['account'];
                                $money = $this->getPlayerMoney($currency, $account);
                                $symbol = $this->getSymbol($currency);
                                $sender->sendMessage(TextFormat::GOLD . "$rank -> " . TextFormat::BLUE . "$account with " . TextFormat::YELLOW . "$symbol$money");
                                return true;
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Currency doesn't exist!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please choose currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.default!");
                return false;
            }
        }
        if (strtolower($command->getName()) == "pay") {
            if ($sender->hasPermission("eco.default")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($args[1])) {
                            if (isset($args[2])) {
                                $account = $sender->getName();
                                $payto = $args[2];
                                $currency = $args[0];
                                if (is_numeric ($args[0])) {
                                if ($this->getCurrency($currency)) {
                                    if ($this->getAccount($currency, $account)) {
                                        if ($this->getAccount($currency, $payto)) {
                                            $budget = $args[1];
                                            if ($this->getPlayerMoney($currency, $account) == $budget) {
                                                $this->payMoney($currency, $account, $payto, $budget);
                                                $symbol = $this->getSymbol($currency);
                                                $sender->sendMessage(TextFormat::YELLOW . "You have paid $symbol$budget to $account's account!");
                                                return true;
                                            } else {
                                                $sender->sendMessage(TextFormat::RED . "You don't have enough money!");
                                            }
                                        } else {
                                            $sender->sendMessage(TextFormat::RED . "Account doesn't exist!");
                                        }
                                    } else {
                                        $sender->sendMessage(TextFormat::RED . "Account doesn't exist!");
                                    }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Currency doesn't exist!");
                                }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Set a number!");
                                }
                            } else {
                                $sender->sendMessage(TextFormat::RED . "Please choose who to pay");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Please set amount of money!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please set currency!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be In-Game!");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Don't have permission: eco.default!");
                return false;
            }
        }
        return false;
    }

}