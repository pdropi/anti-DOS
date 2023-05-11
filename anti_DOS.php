<?php
// ------------Parâmetros de configuração -------------
$key_acessos = '202306'; //Por segurança, alterar pra qualquer número inteiro. É a chave única para acessar os dados de acesso em memória
$key_blocks = '202307'; //Por segurança, alterar pra qualquer número inteiro. É a chave única para acessar os dados de bloqueios em memória
$dado_atual  = $_SERVER['REMOTE_ADDR']; //dado cujas repetições serão limitadas. pode ser outro, ex: $_SERVER['HTTP_USER_AGENT']
$tamanho_max_acessos = 80000; //tamanho em bytes reservados na memória pra armazenar os dados dos acessos
$tamanho_max_blocks = 6000; //tamanho em bytes reservados na memória pra armazenar os dados dos bloqueios
$quebra_de_linha = '|&'; //string usada como "quebra de linha". Na verdade não tem quebra de linha, é uma única grande string. Se for verificação de  user_agent, não usar tradicional \n porque alguns user_agent tem isso no nome. Usar algo mais complexo
$separacao_dos_dados = '|$'; //separa os dados, ex: horadeacesso e ip
$intervalo = 0.9; //intervalo em segundos. pode ser quebrado,  ex: 1.5 igual a um segundo e meio
$qtd_max = 4; //quantidade máxima de ocorrências dentro do intervalo
$tempo_bloqueio = 15; //tempo de bloqueio em segundos
//para evitar prejudicar performance, só usa disco pra eventual log do que é bloqueado e desbloqueado. não deixar em local público ou visível na web
//caso queira usar somente a memória, sem disco, comentar as linhas com file_put_contents
$caminho_log = '/var/log/anti_DOS.log';

// ---------- fim parâmetros de configuração ---------------
$log='';

//--------verifica e refaz lista de acessos dentro do intervalo --------------
$inicio_intervalo = hrtime(true) - $intervalo*1e+9; //$intervalo*1e+9 transforma os segundos em nanosegundos
$permanece = ''; //dados que ainda estão dentro do intervalo e continuarão na lista de acessos

$shm_id = shmop_open($key_acessos, "c", 0600, $tamanho_max_acessos);
$shm_data = shmop_read($shm_id, 0, $tamanho_max_acessos);

if (strpos($shm_data, $quebra_de_linha) !== false){
        $count =0;
        foreach (explode($quebra_de_linha, $shm_data) as $acesso) {
                if (strpos($acesso, $separacao_dos_dados) !== false){
                        list($tempo,$dado_checar) = explode($separacao_dos_dados, $acesso);
                        if($tempo > $inicio_intervalo){
                                $permanece .= $acesso.$quebra_de_linha;
                                if($dado_checar == $dado_atual) $count ++;
                        }
                }
        }
}
//str_pad pra preencher com nulo o espaço restante, se a string nova for menor, pois, shmop não substitui dados antigos na escrita
shmop_write($shm_id, str_pad($permanece.hrtime(true).$separacao_dos_dados.$dado_atual.$quebra_de_linha, $tamanho_max_acessos,"\0"), 0);

//if($_SERVER['REMOTE_ADDR'] == '2804:214:8019:8215:79f5:2ad5:ae38:1662') echo "acessos: $shm_data "; //exibir lista de acessos no intervalo

//--------verifica e refaz lista de bloqueios dentro do intervalo --------------

$inicio_intervalo = hrtime(true) - $tempo_bloqueio*1e+9; //htrtime conta em nanosegundos *1e+9 é pra transformar o tempo de bloqueio em nano
$permanece = ''; //dados que ainda estão dentro do intervalo e continuarão na lista de bloqueios
$renova_bloqueio = false;
$ainda_bloqueado = false;

$shm_id = shmop_open($key_blocks, "c", 0600, $tamanho_max_blocks);
$shm_data = shmop_read($shm_id, 0, $tamanho_max_blocks);

if (strpos($shm_data, $quebra_de_linha) !== false){
        foreach (explode($quebra_de_linha, $shm_data) as $linha_bloqueado) {
                if (strpos($linha_bloqueado, $separacao_dos_dados) !== false){
                        list($tempo,$dado_checar) = explode($separacao_dos_dados, $linha_bloqueado);
                        if($tempo > $inicio_intervalo){ //mantém os que ainda estão no intervalo
                                if($dado_checar == $dado_atual){
                                        if($count >= $qtd_max) $renova_bloqueio = true; //nao fica repetindo entrada na lista ao renovar o block
                                        else {$ainda_bloqueado = true; $permanece .= $linha_bloqueado.$quebra_de_linha;}
                                }else $permanece .= $linha_bloqueado.$quebra_de_linha;
                        }else $log .= date("d/M/Y H:i:s") . ' - ' . $dado_checar . ' - removido'.PHP_EOL; //tempo de block passou
                }
        }
}

if ($count >= $qtd_max){
        $permanece.= hrtime(true).$separacao_dos_dados.$dado_atual.$quebra_de_linha;
        //$log .= date("d/M/Y H:i:s") . ' - ' . $dado_atual . ($renova_bloqueio? ' - bloqueio renovado':' - bloqueado').PHP_EOL; //loga renovação
        //abaixo opção à linha acima sem inserir renovação de block no log, economizando cpu e disco
        if (!$renova_bloqueio) $log .= date("d/M/Y H:i:s") . ' - ' . $dado_atual .' - bloqueado'.PHP_EOL;
}

shmop_write($shm_id, str_pad($permanece, $tamanho_max_blocks,"\0"), 0);

if($log)file_put_contents($caminho_log, $log, FILE_APPEND); //escreve no log
//if($_SERVER['REMOTE_ADDR'] == '2804:214:8019:8215:79f5:2ad5:ae38:1662') echo "bloqueados: $shm_data "; //exibir bloqueados
if($ainda_bloqueado or $count >= $qtd_max) {header('HTTP/1.1 429'); die( "Too Many Requests\n");}
