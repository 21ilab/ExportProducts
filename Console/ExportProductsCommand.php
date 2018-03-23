<?php
/**
 * Created by Afroze.S.
 * Date: 30/1/18
 * Time: 12:07 PM
 */

namespace Twentyone\ExportProducts\Console;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ExportProductsCommand extends Command
{

    protected $path, $storeId, $attributes, $labels, $delimiter, $encapsulator, $collectionFactory;

    /**
     * Inject CollectionFactory(products) so to query products of magento and filter
     *
     * CronProductsCommand constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct();
    }

    /**
     * Configure console command and arguments and options required
     */
    protected function configure() {
        $this->setName('Twentyone:ExportProducts');
        $this->setDescription('Export products from magento catalog');
        $this->setHelp("This command helps to export products in CSV format");
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to save csv file');
        $this->addArgument('store', InputArgument::REQUIRED, 'Magento store');
        $this->addArgument('attributes', InputArgument::REQUIRED, 'Magento produt attribtes');
        $this->addArgument('labels', InputArgument::REQUIRED, 'Labels to be used in CSV');
        $this->addArgument('delimiter', InputArgument::REQUIRED, 'Delimiter to be used in CSV');
        $this->addArgument('encapsulator', InputArgument::REQUIRED, 'Encapsulator');
    }

    /**
     * This function is executed after the console command is types in terminal
     * get the user entered arguments and options and do the magic
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->path = $input->getArgument('path');
        $this->storeId = $input->getArgument('store');
        $output->writeln("Process started to export products from store: ".$this->storeId."\n");
        $this->attributes = $input->getArgument('attributes');
        $this->labels = $input->getArgument('labels');
        $this->delimiter = $input->getArgument('delimiter');
        $this->encapsulator = $input->getArgument('encapsulator');
        $productsCollection = $this->getProducts($this->storeId);
        $attributes = explode(',', $this->attributes);
        $labels = explode(',', $this->labels);
        $products = $this->prepareProductsWithLabels($productsCollection, $attributes, $labels);

        //save CSV
        try {
            $this->makeCSVWithProducts($this->path, $products, $labels, $this->delimiter, $this->encapsulator);
            $output->writeln("File is saved in below line\n".__DIR__."/".$this->path);
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

    /**
     * Get products by store ID
     * from CollectionFactory and return Collection of products
     *
     * @param int $storeId
     * @return Collection
     */
    private function getProducts($storeId) {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addStoreFilter($storeId);

        //$collection->setPage(1, 3);
        /*
        foreach ($collection as $product) {
            var_dump($product->getData());die;
        }
        */

        return $collection;
    }

    /**
     * @param Collection $collection
     * @param array $attributes
     * @param array $labels
     * @return array
     */
    private function prepareProductsWithLabels(Collection $collection, $attributes, $labels) {
        $returnProducts = [];
        foreach ($collection as $product) {
            $prod = [];
            foreach ($attributes as $key => $attribute) {
                if (isset($product[$attribute])) {
                    $prod[$labels[$key]] = $product[$attribute];
                } else {
                    $prod[$labels[$key]] = '';
                }
            }
            $returnProducts[] = $prod;
        }
        return $returnProducts;
    }

    /**
     * @param string $path
     * @param array $products
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function makeCSVWithProducts($path, $products, $labels, $delimiter, $encapsulator) {
        $doc = new Spreadsheet();
        $sheet = $doc->getActiveSheet();
        $sheet->fromArray($labels);
        $sheet->fromArray($products,null, 'A2');
        $csv = new Xls($doc);
        //$csv->setDelimiter($delimiter);
        //$csv->setEnclosure($encapsulator);
        $csv->save(__DIR__."/".$path);
    }

}
