<?php

namespace Seb\App\UseCases\Ticket\EmitTicket;

use DateTime;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Seb\Adapters\Libs\Ticket\PDFGenerator\DTO\GenerateTicketPDFInput;
use Seb\Adapters\Libs\Ticket\PDFGenerator\TicketPDFGeneratable as TicketPDFGenerator;
use Seb\Adapters\Repo\Balcony\Interfaces\BalconyRepository;
use Seb\Adapters\Repo\Ticket\Interfaces\TicketRepository;
use Seb\App\UseCases\BaseUseCase;
use Seb\App\UseCases\Ticket\EmitTicket\DTO\EmitTicketOutput;
use Seb\Enterprise\Ticket\Entities\TicketEntity;
use Seb\Enterprise\Ticket\ValueObjects\TicketStatusEnum as TicketStatus;


final class EmitTicketUseCase extends BaseUseCase
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private BalconyRepository $balconyRepository,
        private TicketPDFGenerator $pdfGenerator,
        LoggerInterface $logger,
    ) {
        parent::__construct($logger);
    }

    public function execute(): EmitTicketOutput
    {
        $isSomeBalconyActive = $this->balconyRepository->verifyActiveBalconies();
        if (!$isSomeBalconyActive) throw new Exception('there is no any active balcony');

        $ticketsFollowing = $this->ticketRepository->readTicketsByStatus(TicketStatus::Pending);

        $ticket = $this->ticketRepository->readLastInsertedTicket();
        $ticketCode = empty($ticket) ? 99 : $ticket['code'];

        $ticket = new TicketEntity();
        $ticket->setCode(++$ticketCode)
            ->setStatus(TicketStatus::Pending)
            ->setEmitionMoment(new DateTime());
            
        $insertedTicket = $this->ticketRepository->createTicket(
            $ticket->getEmitionMoment(),
            $ticket->getCode(),
            $ticket->getStatus(),
        );

        $ticketEmitionMoment = $insertedTicket['emition_moment'];
        unset($insertedTicket['emition_moment']);
        $ticketEmitionMoment = new DateTimeImmutable($ticketEmitionMoment);

        $insertedTicket['date'] = $ticketEmitionMoment->format('d/m/Y');
        $insertedTicket['time'] = $ticketEmitionMoment->format('H:i:s');
        $insertedTicketMessage = 'obedeça a fila...';

        $pdfCode = $this->pdfGenerator->generate(
            new GenerateTicketPDFInput(
                $insertedTicket['code'],
                $insertedTicket['date'],
                $insertedTicket['time'],
                $insertedTicketMessage,
                count($ticketsFollowing),
            )
        );
        
        $emitTicketOutput = new EmitTicketOutput(
            $insertedTicket['code'],
            $pdfCode,
            $insertedTicket['date'],
            $insertedTicket['time']
        );

        $this->logger->notice("created ticket $insertedTicket[code]");

        return $emitTicketOutput;
    }
}
