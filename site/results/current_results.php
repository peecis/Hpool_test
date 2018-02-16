<?php
$defaultalgo = user()->getState('yaamp-algo');
echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>Pool Status</div>";
echo "<div class='main-left-inner'>";
showTableSorter('maintable1', "{
	tableClass: 'dataGrid2',
	textExtraction: {
		4: function(node, table, n) { return $(node).attr('data'); },
		8: function(node, table, n) { return $(node).attr('data'); }
	}
}");
echo <<<END
<thead>
<tr>
<th>Algo</th>
<th></th>
<th data-sorter="numeric" align="right">Port</th>
<th data-sorter="numeric" align="right">Coins</th>
<th data-sorter="numeric" align="right">Miners</th>
<th data-sorter="numeric" align="right">Hashrate</th>
<th data-sorter="currency" align="right">Fees</th>
</tr>
</thead>
END;
$best_algo = '';
$best_norm = 0;
$algos = array();
foreach(yaamp_get_algos() as $algo)
{
	$algo_norm = yaamp_get_algo_norm($algo);
	$price = controller()->memcache->get_database_scalar("current_price-$algo",
		"select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
	$norm = $price*$algo_norm;
	$norm = take_yaamp_fee($norm, $algo);
	$algos[] = array($norm, $algo);
	if($norm > $best_norm)
	{
		$best_norm = $norm;
		$best_algo = $algo;
	}
}
function cmp($a, $b)
{
	return $a[0] < $b[0];
}
usort($algos, 'cmp');
$total_coins = 0;
$total_miners = 0;
$showestimates = false;
echo "<tbody>";
foreach($algos as $item)
{
	$norm = $item[0];
	$algo = $item[1];
	$coins = getdbocount('db_coins', "enable and visible and auto_ready and algo=:algo", array(':algo'=>$algo));
	if (!$coins) continue;
	
	$workers = getdbocount('db_workers', "algo=:algo", array(':algo'=>$algo));
	$hashrate = controller()->memcache->get_database_scalar("current_hashrate-$algo",
		"select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
	$hashrate_sfx = $hashrate? Itoa2($hashrate).'h/s': '-';
	//$price = controller()->memcache->get_database_scalar("current_price-$algo",
	//	"select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
	//$price = $price? mbitcoinvaluetoa(take_yaamp_fee($price, $algo)): '-';
	//$norm = mbitcoinvaluetoa($norm);
	//$t = time() - 24*60*60;
	//$avgprice = controller()->memcache->get_database_scalar("current_avgprice-$algo",
	//	"select avg(price) from hashrate where algo=:algo and time>$t", array(':algo'=>$algo));
	//$avgprice = $avgprice? mbitcoinvaluetoa(take_yaamp_fee($avgprice, $algo)): '-';
	//$total1 = controller()->memcache->get_database_scalar("current_total-$algo",
	//	"SELECT SUM(amount*price) AS total FROM blocks WHERE time>$t AND algo=:algo AND NOT category IN ('orphan','stake','generated')",
	//	array(':algo'=>$algo)
	//);
	//$hashrate1 = controller()->memcache->get_database_scalar("current_hashrate1-$algo",
	//	"select avg(hashrate) from hashrate where time>$t and algo=:algo", array(':algo'=>$algo));
	//$algo_unit_factor = yaamp_algo_mBTC_factor($algo);
	//$btcmhday1 = $hashrate1 != 0? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000 * $algo_unit_factor): '';
	$fees = yaamp_fee($algo);
	$port = getAlgoPort($algo);
	if($defaultalgo == $algo)
		echo "<tr style='cursor: pointer; background-color: #e0d3e8;' onclick='javascript:select_algo(\"$algo\")'>";
	else
		echo "<tr style='cursor: pointer' class='ssrow' onclick='javascript:select_algo(\"$algo\")'>";
	echo "<td><b>$algo</b></td>";
	echo "<td width=18></td>";
	echo "<td align=right style='font-size: .8em;'></td>";
	echo "<td align=right style='font-size: .8em;'></td>";
	echo "<td align=right style='font-size: .8em;'>$workers</td>";
	echo '<td align="right" style="font-size: .8em;" data="'.$hashrate.'">'.$hashrate_sfx.'</td>';
	echo "<td align=right style='font-size: .8em;'>{$fees}%</td>";
	
	echo "</tr>";
	
	// ---------------------------------- coin code here ---------------------------------

	$coin_list = getdbolist('db_coins', "enable and visible and auto_ready and algo=:algo", array(':algo'=>$algo));
	foreach($coin_list as $dinero)
	{
		$coin_name = $dinero->symbol;
		$coin_port = $dinero->symbol2;

		$pool_hash = yaamp_coin_rate($dinero->id);
		$coin_hash = $pool_hash? Itoa2($pool_hash).'h/s': '';

		echo "<tr>";
		echo "<td align=center>$coin_name</td>";
		echo "<td width=18><img width=16 src='$dinero->image'></td>";
		echo "<td align=right style='font-size: .8em;'>$coin_port</td>";
		echo "<td align=right style='font-size: .8em;'></td>";
		echo "<td align=right style='font-size: .8em;'></td>";
		echo "<td align=right style='font-size: .8em;'>$coin_hash</td>";
		echo "<td align=right style='font-size: .8em;'></td>";
		echo "</tr>";
	}
	
	// --------------------------------- end of coin list -------------------------------- 

	$total_coins += $coins;
	$total_miners += $workers;
}
echo "</tbody>";
if($defaultalgo == 'all')
	echo "<tr style='cursor: pointer; background-color: #e0d3e8;' onclick='javascript:select_algo(\"all\")'>";
else
	echo "<tr style='cursor: pointer' class='ssrow' onclick='javascript:select_algo(\"all\")'>";
echo "<td><b>all</b></td>";
echo "<td></td>";
echo "<td></td>";
echo "<td align=right style='font-size: .8em;'>$total_coins</td>";
echo "<td align=right style='font-size: .8em;'>$total_miners</td>";
echo "<td></td>";
echo "<td></td>";
echo "</tr>";
echo "</table>";
echo "</div></div><br>";
?>

<?php if (!$showestimates): ?>

<style type="text/css">
#maintable1 .estimate { display: none; }
</style>

<?php endif; ?>


