<?php
/*
 * @name: whitebotsh
 * @desc: Integra la funcionalidad de whitebotsh en coot
 * @ver: 1.0
 * @author: MRX
 * @id: whitebotvoy
 * @key: key

 */
class key{
    private $timehandlerid;
    private $hablar=true;
    private $ts;
   // private $admins=array("siglar", "White_Master", "zerabat", "IrwinSantos");
	public function __construct(&$core){
        try {
			$k = ORM::for_table('chignore2')->find_one();
		}catch(PDOException $e){
			$query="CREATE TABLE 'chignore2' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'user' TEXT NOT NULL);";
			$db = ORM::get_db();
			$db->exec($query);
		}
		//$this->timehandlerid = $core->irc->registerTimehandler(20000, $this, "th");
		$core->registerTimeHandler(20000, "whitebotvoy", "th");
		//$core->registerCommand("admin", "whitebotsh", "Avisa a los administradores disponibles.");
		$core->registerCommand("talk", "whitebotvoy", "Habilita o deshabilita el habla del bot. Sintaxis: hablar <si/no>",5,"whitesh");
		
		$core->registerCommand("chignore", "whitebotsh", "Ignora un usuario en los cambios recientes. Sintaxis: chignore <usuario>",5,"whitesh");
		$core->registerCommand("dechignore", "whitebotsh", "Designora un usuario en los cambios recientes. Sintaxis: chignore <usuario>",5,"whitesh");
	}
	
	public function chignore(&$irc, &$data, &$core){
		$s = ORM::for_table('chignore')->create();
		$s->user = $data->messageex[1];
		$s->save();
	}
	
	public function dechignore(&$irc, &$data, &$core){
		$s = ORM::for_table('chignore')->where('user', $messageex[1])->find_one();
		if(method_exists($s, "delete")){$s->delete();}
	}
	
	public function talk(&$irc, &$data, &$core){
		if(!isset($data->messageex[1])){return 0;}
		switch($data->messageex[1]){
			case "yes":
			case "y":
				$this->hablar = true;
				$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "Back in action! ;3");
				break;
			case "no":
			case "n":
				$this->hablar = false;
				$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "Will keep quiet :(");
			
		}
	}
	
	public function admin(&$irc, &$data, &$core){
		foreach($this->admins as $admin){
			$irc->message(SMARTIRC_TYPE_CHANNEL, $admin, "\002{$data->nick}\002 ha solicitado la ayuda de un administrador en {$data->channel}");
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "Se ha enviado un aviso a los administradores disponibles.");
	}
    
    function th(&$irc){
        $fl=file_get_contents("http://sh.wikipedia.org/w/api.php?action=query&list=recentchanges&format=json&rcprop=user|comment|flags|title|timestamp|loginfo");
        $fg=json_decode($fl);
        $s="";
        print_r($fg);
        if(($this->ts != $fg->query->recentchanges[0]->timestamp) && ($this->hablar==true)){
            $this->ts = $fg->query->recentchanges[0]->timestamp;
            
            switch($fg->query->recentchanges[0]->type){
                case "edit":
					if(isset($fg->query->recentchanges[0]->bot)){break;}
					$p = ORM::for_table('chignore')->where('user', $fg->query->recentchanges[0]->user)->find_one();
					if($p->user){break;}
                    $s="03".$fg->query->recentchanges[0]->user." has edit \00310[[".$fg->query->recentchanges[0]->title."]]\003 with the following comment: 07".$fg->query->recentchanges[0]->comment;
                    if(@isset($fg->query->recentchanges[0]->minor)){ $s.="11 This is a minor edit."; }
						$s.=" \00311https://sh.wikipedia.org/w/index.php?title=".urlencode(str_replace(" ","_", $fg->query->recentchanges[0]->title))."&diff";
                    break;
                case "new":
					$p = ORM::for_table('chignore')->where('user', $fg->query->recentchanges[0]->user)->find_one();
					if($p->user){break;}
                    $s="03".$fg->query->recentchanges[0]->user." has create \00310[[".$fg->query->recentchanges[0]->title."]]\003 with the following comment: 07".$fg->query->recentchanges[0]->comment;
                    break;
                case "log":
					switch ($fg->query->recentchanges[0]->logtype){
						case "rights":
							if($fg->query->recentchanges[0]->rights->old==""){$old="(none)";}else{$old=$fg->query->recentchanges[0]->rights->old;}
							if($fg->query->recentchanges[0]->rights->new==""){$new="(none)";}else{$new=$fg->query->recentchanges[0]->rights->new;}
							$s="\00303{$fg->query->recentchanges[0]->user}\003 changed permissions \002[[".$fg->query->recentchanges[0]->title."]]\002 of \00304".$old."\003 to \00304".$new."\003 con el siguiente comentario: \00307".$fg->query->recentchanges[0]->comment."\003";
							break;
						 case "block":
							$s="\00303{$fg->query->recentchanges[0]->user}\003 locked \002{$fg->query->recentchanges[0]->title}\002. Duration: \002{$fg->query->recentchanges[0]->block->duration}\002. Reason: \00307{$fg->query->recentchanges[0]->comment}";
							break;
						 case "delete":
							$s = "\00303{$fg->query->recentchanges[0]->user}\003 delete \002{$fg->query->recentchanges[0]->title}\002: \00307{$fg->query->recentchanges[0]->comment}\003";
							break;
						
					}
            }
            $irc->send("PRIVMSG #wikivoyage-es :$s");
        }

    }
}
