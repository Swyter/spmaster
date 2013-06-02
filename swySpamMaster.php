<?php
/**
  * .swySpamMaster extension by Swyter
  * Adds a simple spam/bot check to registrations
  */

if(!defined('MEDIAWIKI'))
{
	die("<center><code>Okay, from now on you're the coolest boy on &lt;insert subject home town here&gt;<br/>--swyter");
}

$wgExtensionCredits['antispam'][] = array(
	'path'           => __FILE__,
	'name'           => '.swySpamMaster',
	'description'    => '<em>Mount&Blade Modding Wiki</em> custom antibot goodness',
	'author'         => 'Swyter',
	'url'            => 'https://swyterzone.appspot.com',
	'version'        => 'φ',
);

$wgHooks['AbortNewAccount'][] = function( $user, &$message )
{
    global $username;
    
    $username = $user->getName();
    $entropy  = 0;
    $points   = 0;
    $diag     = '';
     
    function spmatch($w)
    {
      global $username;
      return preg_match_all($w,$username,$match);
      #printf("%s user:%s match:%s\r\n",$w,$username,$match);
    }
    
    #From SO: http://stackoverflow.com/a/3198026/674685
    function entropy($string)
    {
       $h=0;
       $size = strlen($string);
       foreach (count_chars($string, 1) as $v) {
          $p = $v/$size;
          $h -= $p*log($p)/log(2);
       }
       return $h;
    }
    
    #From SO: http://codereview.stackexchange.com/a/13559; adapted w/ ternary op
    function df($var, $def='?')
    {
      return empty($var) ? $def : $var;
    }
    
  /*
    PATTERN COMBINATIONS
    
    
    -NEW- Namesurname0000
     Roycejuarez3228
     Franklinmorse6966
    -----
     [A-Z][a-z]{6,15}[0-9]{1,4}
  */

    spmatch('/^[A-Z][a-z]{6,15}[0-9]{1,4}$/')==1
     and $diag.="<li>Namesurname0000" and $points++;
    
    
  /*
    -NEW- NameDict{Random}SurnameDict, large size, lots of repeated non-vocals
     AwildarpovcvayttIberg
     WesleyagonuohkkrKrise
     LesliegdzfjpsaucCushway
     NakeshajkujlzuuluOverton
    -----
     [^aeiou\s\d]{3,}
  */
  
    spmatch('/^[A-Z][a-z]{6,15}[a-z]*[^aeiou\s\d]{3,}[a-z]*[A-Z][a-z]{6,15}$/')==1 and
    spmatch('/[^aeiou\s\d]{3,}/')>1
     and $diag.="<li>NameDict{Random}SurnameDict" and $points++;

  /*
    -NEW- NameSurname000{2,3}
     McclainKendrick675
    -----
     ([A-Z][a-z]+){2}[0-9]{2,3}
  */
  
    spmatch('/^([A-Z][a-z]+){2}[0-9]{2,3}$/')==1
     and $diag.="<li>NameSurname000{2,3}" and $points++;
    
  /* 
    -NEW- Random 6-10char, lots of repeated non-vocals
     Vxkajx    -- 1/5 -- 6  -- a    -- Vxkjx
     Lbovlne   -- 2/5 -- 7  -- oe   -- Lbvln
     Fvigaeur  -- 4/4 -- 8  -- iaeu -- Fvgr
     Orfuuwsil -- 4/5 -- 9  -- ouui -- rfsl
     Bqltuyhnuz-- 8/2 -- 10 -- uu   -- Bqltyhnz
     Damiel    -- counterexample
     Swyter    -- counterexample
     TJ        -- counterexample
     Michael   -- counterexample
     Protu     -- counterexample
     Cmpxchg8b -- counterexample
    -----
     ^[A-Z][a-z]{5,9}$
     [^aeiou\s\d]{3,} --entropy
     
  */

    spmatch('/^[A-Z][a-z]{5,9}$/' )==1 and
   (spmatch('/[^aeiouy\s\d]{4,}/' )>=1  or
    spmatch('/[^aeiouy\s\d]{3}/'  )>=2)
     and $diag.="<li>Random 6-10char, lots of repeated non-vocals" and $points++;


  /*
    -NEW- Entropy calculation
  */
  
    $entropy = entropy($username);
    $entropy <= 3.4 or
    $entropy >= 0.4
     and $diag.="<li>Entropy out of threshold" and $points++;
     
  /*
    -NEW- Repeating chars
  */
    
    $size = strlen($username);
    foreach (count_chars($username, 1) as $v)
    {
       if ($v/$size > .4)
       {
          $diag.="<li>So many repeated chars" and $points++;
          break;
       }
    }
       
    /* <style> ftw! it's official, mediawiki sucks ® */
    printf("<style>        div.errorbox { background:      #FFB url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAATIAAACACAMAAAHIaiWrAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAtxQTFRFaT4fimpNeU0plIR8dUkmgVoxhHJqa00p8+7sg3RtbUEh2srCbEgmpJOK1cW9fGxj7+fjYz0f7eLc7N7XcUUjuaifcU4pfGM47+jkm4qC69zV7uXha0AgyLiwcEklgnJo693W6trSflUtZUAhdE4p5NPLaUUkpox17ujlrnKSfVMydFoy8Ornx7Wtek8quqmgtKObZUYlvHSieVUthHRsjHpybUYkrJqRfFEqbkQidlQubVAsf25mzb228evo////c1cweVkxx7atfnBpvq2ldVEslHRaf2RPgXVvYj8hdlAq49rUeV81fVcvg2A5sJiGm39neEsnqpmQfVkwgXBnr52VeFtEclEs8u3q69vUuqqiwLGpZUgmwHSnaEoob0onvKuick8rildLakQjelwzeFIrfF0+f10ycVQuyrmssZ+VdkwnwrClvbKsw6ycy7y1i3lvbUEdpGR37efkdk0uhnhysqGYyrqyv66mgXJstqmi5tjRxLm07ebiuKigyLmxuqqhzr+4yLqyh3Zut6Wd6uLdzLuzY0QkjnJ0dksn0cC3fVIrpJiSr6CZyru0ZUMjvayjblUvj393yLKkckos8ezpe1AnfV41597YgFg18ezq4dXPxLOquaWWxrSrwrStmWZmdEssc04rZ0Mj4tHIqZ+Z6uTgsqagfFc66eDbyrmw0buucUwooI+GbUcpu6ylq5yUf1Usd08vdlM3elg2ckckhXNqhHNrnHOCe1QseFk9xrevY0IjYT0f6NbOt6umuKqiaUIi9vTy9/X09/Tz9fLw9PHv7eHb8Onm9PDu8uzp8u7r7+bh7uTf7ODa9vPx9fHv7ePd6djP6tnR6dfO9fHw7uPerZuT6NbN7OXhd1cw8+/t7OXi6+Tg9/XzdEgl7+vo9/TyzLqwzr6yup+M3c7H6OHd8e7rkXhodU4jblAxp5aOiHx259rTqJqU9PDt9PDv+PXz+PX0////jvhlegAAAPR0Uk5T////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////ABjg9xcAADMbSURBVHjaYvhMHGDAKnoGXWAfFnWnINQ5IL4MVAGGOMyDqIv8/DmBje3z55DP+7CZ91kHai+nyfaQVaJsn0P4sdoLB72ysk66oSFsQDPxqeNcW9egz2YeGvKZDd199hBK4vNZj8+Vb+pNGvRFRc3Z/FksGXCZ98ZjEmd9FESd3v2tB7GaBwIen8HqJEXNC9z07otgNU8CiBnFSxvLt29nC5UEGqh3nwHTuNbPn9WSk9fosdrIbjcy0ty8VY+FKRDdvKOz1yS6ruFRO5i8JpVTlt+3k+sgl0gnSyeqOus4T08enngrOfbkg2aJFqwbLSy2AhVydSKp8xFP93GIM/T0XMwTnyq3JlnZLDvbF6hQhItLhguq7klMTBx7ho+jjwOPofLexWYqKnJyB5OBCpU2WmRbHpSR4QIIIAYykh889KQhqQqc8PCkUmlE8gSmKKzKIkDENSgnBBj/n9n24Ukrwo0m27dPEp3Aj5lUUICCrexu3S5zUMrDrkwAnOA586P0gSmP7XMBP07TPHI9mkyYwenps56eF05lHh75u0HJLjSUjYuFBU3ZIYil0sBU97lStkHfZS3QOBmu+zi9sEaOU3b79u3yIeZsB2W4sCpbMz3FyyueU9ZG3siochqLpQxmnhDj8dyrtiY42MvK43EXMJXoMemJcMmgKFP3cXR05VHbq8zuFRy805jF0nLrfSYmoDpLhDKxuHZD6/BwX2Dy3Xsw0Ss1ddqmrVyWW0WA6pigGUcbnChjDA3Zw8Md15gp7z0YvyaVdZoeSJ1l58uXTAABRGR6g4JryJzLny+B0xkSIt40AXQBoGlXPqMkXlymIRUWt8EJ9QS44EW4LVIRSADd9llyH8yoz58Ju03jMwfMbdLQklcYRHGHAGN8OxtbyOdtZZ8/b2MjwrRDGPnt+HFYEV2Zs8pJVzdHVJSNjZkNnBNwl8L2OMMNmM85ORtlQSDHgI3t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) no-repeat bottom right !important;
                                          color:           #846 !important;
                                          border-color:    #645 !important;
                                          padding-right:  135px !important;
                                          
                                          transition: all .5s linear !important; /* because i can! */
                                        }

                     div.errorbox:hover { background-size: 40%% !important;
                                          opacity: .7 !important; }

                        div.errorbox br { display: none !important; }
                         div.errorbox p { display: inline-block; }
  div.errorbox>strong:first-child:after { content: ':'; }
                                      * { color: inherit; } /* lolwtfbbq? */ </style>");

    $message ="<strong>Looks like you have a spammey name, how rude :)</strong>
               <hr/><em>$username</em> | has $entropy of entropy, $points points, ". ($points>=1 ? "banned" : "good guy") ."
               <br/><ol>$diag</ol>";
               
    $h=fopen(dirname(__FILE__)."/_".($points>=1 ? "block" : "pass")."list.log", 'a' ) or die("Cannot write the bot block log");
       fwrite($h,utf8_encode(sprintf("\n%s%s\n\n     ip: %s\n    uag: %s\n   fwip: %s\n",date("Y-m-d h:i:s A |"),$username,df($_SERVER['HTTP_USER_AGENT']),df($_SERVER['REMOTE_ADDR']),df($_SERVER['HTTP_X_FORWARDED_FOR']))));
       fclose($h);
    

    return($points>=1 ? false : true);
};
?>