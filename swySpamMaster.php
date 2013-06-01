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
    function df($var, $def)
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
       
    
    $message ="<center><strong>Looks like you have a spammey name, how rude :)</strong></center>
               <hr/><em>$username</em> | has ".$entropy." of entropy, $points points, ". ($points>=1 ? "banned" : "good guy") ."
               <br/><ol>$diag</ol>";
               
    $h=fopen(dirname(__FILE__)."/_".($points>=1 ? "block" : "pass")."list.log", 'a' ) or die("Cannot write the bot block log");
       fwrite($h,utf8_encode(sprintf("\n%s%s\n\n     ip: %s\n    uag: %s\n   fwip: %s\n",date("Y-m-d h:i:s A |"),$username,df($_SERVER['HTTP_USER_AGENT'], '?'),df($_SERVER['REMOTE_ADDR'], '?'),df($_SERVER['HTTP_X_FORWARDED_FOR'], '?'))));
       fclose($h);
    

    return($points>=1 ? false : true);
};
?>