<?php

use Dompdf\Dompdf;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Seb\Adapters\DB\PDO\MySQL\MySQLPDOConnector;
use Seb\Adapters\Libs\Ticket\PDFGenerator\DomPDF\DomPDFTicketGeneratorAdapter;
use Seb\Adapters\Repo\Balcony\MySQL\PDOBalconyRepository;
use Seb\Adapters\Repo\Ticket\MySQL\PDOTicketRepository;
use Seb\App\UseCases\Ticket\EmitTicket\EmitTicketUseCase;
use Seb\Platform\Web\Ticket\Controller\TicketController;
use Slim\App;

return function (App $app) {
    $app->get('/emit_ticket', function (Request $request, Response $response, $args) {
        $mysqlConnector = new MySQLPDOConnector('mysql:host=localhost;dbname=seb', 'root', '');
    
        $domPDF = new Dompdf();
        $ticketPdfGen = new DomPDFTicketGeneratorAdapter($domPDF);
        $createTicketRepo = new PDOTicketRepository($mysqlConnector->getConnection());
        $balconyRepo = new PDOBalconyRepository($mysqlConnector->getConnection());
        $emitTicketUseCase = new EmitTicketUseCase($createTicketRepo, $balconyRepo, $ticketPdfGen);

        $controller = new TicketController($request, $response);
        $response = $controller->emitTicket($emitTicketUseCase);

        return $response;
    });
};
