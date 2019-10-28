<?php

class AlgoritimoGenetico {

    protected $probabilidadeCruzamento = 100;
    protected $probabilidadeMutacao = 2;
    protected $tamanhoPopulacao = 20;
    protected $tamanhoCromossomo;

    /**
     * @var array
     */
    protected $rotas;

    /**
     * @var array
     */
    protected $populacao;

    public function __construct()
    {

        $rotas = file_get_contents('rotas');

        foreach (explode("\n", $rotas) as $linha) {

            if(trim($linha) == "") continue;

            $this->_interpretarLinha($linha);

        }

    }

    private function _interpretarLinha($linha){

        $linha = array_map('trim', explode(";", $linha));

        $this->tamanhoCromossomo = $linha[0];

        $rotas = [];

        for($i = 1; $i <= $this->tamanhoCromossomo; $i++){

            if($i == $this->tamanhoCromossomo){

                $rotas["$i-1"] = $linha[$i];

            } else {

                $rotas["$i-" . ($i + 1)] = $linha[$i];

            }

        }

        $posicao = $this->tamanhoCromossomo;

        for($i = 1; $i <= $this->tamanhoCromossomo; $i++){

            for($j = 1; $j <= $this->tamanhoCromossomo; $j++){

                if(!isset($rotas["$i-$j"]) && !isset($rotas["$j-$i"]) && $i != $j){

                    $posicao++;

                    $rotas["$i-$j"] = $linha[$posicao];

                }

            }

        }

        $this->rotas = $rotas;

        echo "Iniciando a população\n";
        $this->_iniciarPopulacao();

        echo "Avaliando indivíduos\n";
        $this->_avaliarIndividuos();

        $k = 1;

        while ($k <= 200){

            $this->_gerarProximaGeracao();
            $k++;

        }

        $melhorRota = implode(" - ", $this->populacao[0]['cromossomo']);

        echo "Melhor Rota para {$this->tamanhoCromossomo} cidades: {$melhorRota}\nDistância: {$this->populacao[0]['fitness']}\n\n\n";

    }

    private function _iniciarPopulacao(){

        $this->populacao = array_map(function(){

            return ['cromossomo' => $this->_gerarCromossomo(), 'fitness' => 0];

        }, range(1, $this->tamanhoPopulacao));

    }

    private function _gerarProximaGeracao(){

        $this->_novaPopulacaoPorTorneio();
        $this->_sortPopulacao();

    }

    private function _novaPopulacaoPorTorneio(){

        $novaPopulacao = [];
        $populacao = $this->populacao;
        shuffle($populacao);

        while(count($novaPopulacao) < $this->tamanhoPopulacao/2){

            $individos = array_splice($populacao, 0, 4);

            usort($individos, function($a, $b){
                return $a['fitness'] > $b['fitness'];
            });

            if($this->_cruzarOuMutar() == 'C'){

                $novosIndividuos = $this->_cruzarIndividuos($individos[0], $individos[1]);


            } else {

                $novosIndividuos = $this->_mutarIndividuos($individos[0], $individos[1]);

            }

            $novaPopulacao[] = $novosIndividuos[0];
            $novaPopulacao[] = $novosIndividuos[1];

        }

        /**
         * Mantendo o melhor da população anterior
         */
        $novaPopulacao[] = $this->populacao[0];

        shuffle($this->populacao);

        while(count($novaPopulacao) < $this->tamanhoPopulacao){

            $novaPopulacao[] = array_pop($this->populacao);

        }

        $this->populacao = $novaPopulacao;

    }

    private function _mutarIndividuos($individuo1, $individuo2){

        $ind1 = $individuo1['cromossomo'];
        $ind2 = $individuo2['cromossomo'];

        unset($ind1[count($ind1) - 1]);
        unset($ind2[count($ind2) - 1]);

        shuffle($ind1);
        shuffle($ind2);

        $ind1[] = $ind1[0];
        $ind2[] = $ind2[0];

        $individuo1['cromossomo'] = $ind1;
        $individuo2['cromossomo'] = $ind2;

        $individuo1['fitness'] = $this->_fitnessFunction($individuo1['cromossomo']);
        $individuo2['fitness'] = $this->_fitnessFunction($individuo2['cromossomo']);

        return [$individuo1, $individuo2];

    }

    private function _cruzarIndividuos($individuo1, $individuo2){

        $ind1 = $individuo1['cromossomo'];
        $ind2 = $individuo2['cromossomo'];

        unset($ind1[count($ind1) - 1]);
        unset($ind2[count($ind2) - 1]);

        $corte = random_int(0, count($ind1));

        $ind1 = implode("", $ind1);
        $ind2 = implode("", $ind2);

        $individuoNovo = substr($ind1, 0, $corte);

        while(strlen($individuoNovo) != $this->tamanhoCromossomo){
            $i = 0;
            while(in_array($ind2[$i], str_split($individuoNovo))) $i++;
            $individuoNovo .= $ind2[$i];
        }

        $ind1 = $individuoNovo;

        $individuoNovo = substr($ind2, $corte);

        while(strlen($individuoNovo) != $this->tamanhoCromossomo){
            $i = 0;
            while(in_array($ind1[$i], str_split($individuoNovo))) $i++;
            $individuoNovo .= $ind1[$i];
        }

        $ind2 = $individuoNovo;

        $ind1 = str_split($ind1);
        $ind2 = str_split($ind2);

        $ind1[] = $ind1[0];
        $ind2[] = $ind2[0];

        $individuo1['cromossomo'] = $ind1;
        $individuo2['cromossomo'] = $ind2;

        $individuo1['fitness'] = $this->_fitnessFunction($individuo1['cromossomo']);
        $individuo2['fitness'] = $this->_fitnessFunction($individuo2['cromossomo']);

        return [$individuo1, $individuo2];

    }

    private function _cruzarOuMutar(){

        $max = $this->probabilidadeCruzamento * 100;
        $max += $this->probabilidadeMutacao * 100;

        if(random_int(1, $max) > $this->probabilidadeCruzamento * 100){

            return "M";

        }

        return "C";

    }

    private function _avaliarIndividuos(){

        $this->populacao = array_map(function($individio){

            $individio['fitness'] = $this->_fitnessFunction($individio['cromossomo']);

            return $individio;

        }, $this->populacao);

        $this->_sortPopulacao();

    }

    private function _gerarCromossomo(){

        $cromossomo = [];

        for($i = 0; $i < $this->tamanhoCromossomo; $i++){

            $num = random_int(1, $this->tamanhoCromossomo);

            while (in_array($num, $cromossomo)){
                $num = random_int(1, $this->tamanhoCromossomo);
            }

            $cromossomo[] = $num;

        }

        $cromossomo[] = $cromossomo[0];
        
        return $cromossomo;

    }

    private function _fitnessFunction($rota){

        $distancia = 0;

        foreach ($rota as $k => $cidade) {

            if($k == 0) continue;

            $cidadeAnterior = $rota[$k-1];

            if(isset($this->rotas["$cidade-$cidadeAnterior"])){

                $distancia += $this->rotas["$cidade-$cidadeAnterior"];

            } else {

                $distancia += $this->rotas["$cidadeAnterior-$cidade"];

            }

        }

        return $distancia;

    }

    private function _sortPopulacao(){

        usort($this->populacao, function($a, $b){
            return $a['fitness'] > $b['fitness'];
        });

    }

}

new AlgoritimoGenetico();