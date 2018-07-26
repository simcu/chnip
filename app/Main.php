<?php
/**
 * Created by IntelliJ IDEA.
 * User: xrain
 * Date: 2018/7/26
 * Time: 20:42
 */

namespace App;


use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Main extends Command
{
    private $_console;

    protected function configure()
    {
        $this->setName("run")->setDescription("创建中国IP列表");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_console = $output;
        $progressBarFormat = ' %current%/%max% [%bar%] %percent:3s%%' . PHP_EOL . '%message%';
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat($progressBarFormat);
        $this->log("创建掩码-主机地址对应表 ...");
        $cidrs = [];
        for ($i = 1; $i <= 32; $i++) {
            $cidrs[pow(2, 32 - $i)] = $i;
        }
        $this->log("开始获取远程APNIC列表 ...");
        $url = 'https://ftp.apnic.net/apnic/stats/apnic/delegated-apnic-latest';
        $content = file_get_contents($url);
        $list = explode(PHP_EOL, $content);
        $this->log("开始处理列表数据 ...");
        $progressBar->setMaxSteps(count($list));
        $progressBar->start();
        $cnList = [];
        foreach ($list as $item) {
            $progressBar->setMessage($item);
            $tmp = explode('|', $item);
            if ($this->arrGet($tmp, 0) == 'apnic' and
                $this->arrGet($tmp, 1) == 'CN' and
                $this->arrGet($tmp, 2) == 'ipv4') {
                $cnList[] = [
                    'start' => $tmp[3],
                    'mask' => $cidrs[$tmp[4]],
                    'location' => $tmp[1]
                ];
            }
            $progressBar->advance();
        }
        $progressBar->setMessage('');
        $progressBar->finish();
        $progressBar = new ProgressBar($output);
        $this->log("成功获取记录" . count($cnList) . "条,开始创建规则文件 ...");
        $progressBar->setMaxSteps(count($cnList));
        $progressBar->setFormat($progressBarFormat);
        $progressBar->start();
        $fileStr = '';
        foreach ($cnList as $item) {
            $txt = 'add chnip ' . $item['start'] . '/' . $item['mask'];
            $progressBar->setMessage($txt);
            $fileStr .= $txt . PHP_EOL;
            $progressBar->advance();
        }
        $adapter = new Local(__DIR__ . '/../');
        $fs = new Filesystem($adapter);
        $fs->put("chnip.ipset", $fileStr);
        $progressBar->setMessage('');
        $progressBar->finish();
        $this->log("规则文件创建完成 ...");
    }

    public function log($str, $type = "INFO")
    {
        $formatter = $this->getHelper('formatter');
        $result = $formatter->formatSection(
            date('Y-m-d H:i:s') . ' ' . $type,
            $str
        );
        $this->_console->writeln($result);
    }

    private function arrGet($arr, $key)
    {
        if (isset($arr[$key])) {
            return $arr[$key];
        } else {
            return false;
        }
    }

}