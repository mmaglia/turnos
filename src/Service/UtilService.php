<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio utilizado para realizar acciones generales del sistema
 * @author jalarcon
 */
class UtilService
{

    private $release;

    public function __construct()
    {
        $this->release = $this->getGitInformation();
    }

    public function getRelease()
    {
        return $this->release;
    }

    public function setRelease($release)
    {
        $this->release = $release;
    }

    /**
     * @return bool|string
     */
    public function getGitInformation()
    {
        // Obtengo la raíz donde corre el sistema
        $path = $_ENV['PATH_SISTEMA'];

        // Si no es un directorio devuelvo false
        if (!\is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
            return false;
        }

        // Comando git a ejecutar
        $process = \proc_open(
            'git describe --tags',
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $path
        );

        if (!\is_resource($process)) {
            return false;
        }

        // Ejecuto el comando y lo guardo en los pipes
        $result = \trim(\stream_get_contents($pipes[1]));

        // Cierrro los pipes
        \fclose($pipes[1]);
        \fclose($pipes[2]);

        // Obtengo el retorno
        $returnCode = \proc_close($process);

        if ($returnCode !== 0) {
            return false;
        }

        return $result;
    }
}
