<?php

namespace Seb\Adapters\Repo\Ticket\MySQL;

use DateTimeInterface as DateTime;
use PDO;
use Seb\Adapters\Repo\Interfaces\PDORepository;
use Seb\Adapters\Repo\Ticket\Interfaces\TicketRepository;
use Seb\Enterprise\Ticket\ValueObjects\TicketStatusValueObject as TicketStatus;

final class PDOTicketRepository extends PDORepository implements TicketRepository
{
    public function createTicket(DateTime $emitionMoment, int $code, TicketStatus $status): array
    {
        $query = '
            insert into tickets set
            code = :code,
            emition_moment = :emition_moment,
            status = :status;
        ';

        $formatetEmitionMoment = $emitionMoment->format('Y-m-d H:i:s');

        $statment = $this->connection->prepare($query);
        $statment->bindParam(':code', $code);
        $statment->bindParam(':emition_moment', $formatetEmitionMoment);
        $statment->bindParam(':status', $status);
        $statment->execute();

        $insertedTicketId = $this->connection->lastInsertId();
        $ticket = $this->readTicketById($insertedTicketId);
        return $ticket;
    }

    private function readTicketById(int $id): array
    {
        $query = '
            select * from tickets where id = :id;
        ';

        $statment = $this->connection->prepare($query);
        $statment->bindParam(':id', $id);
        $statment->execute();

        $ticket = $statment->fetch(PDO::FETCH_ASSOC);
        return $ticket;
    }

    public function readLastInsertedTicket(): array
    {
        $query = '
            select * from tickets order by id desc limit 1;
        ';
        $statment = $this->connection->prepare($query);
        $statment->execute();

        $ticket = $statment->fetch(PDO::FETCH_ASSOC);

        if ($statment->rowCount() === 0) return [];
        return (array) $ticket;
    }

    public function readFirstTicketByStatus(TicketStatus $ticketStatus): array
    {
        $query = '
            select * from tickets where status = :status limit 1;
        ';

        $statment = $this->connection->prepare($query);
        $statment->bindParam(':status', $ticketStatus);
        $statment->execute();

        $ticket = $statment->fetch(PDO::FETCH_ASSOC);
        if ($statment->rowCount() === 0) return [];
        return $ticket;
    }

    public function updateTicketStatus(int $ticketId, TicketStatus $ticketStatus): bool
    {
        $query = '
            update tickets set status = :status where id = :id;
        ';

        $statment = $this->connection->prepare($query);
        $statment->bindParam(':status', $ticketStatus);
        $statment->bindParam(':id', $ticketId);

        return $statment->execute();
    }

    public function readServiceByBalconyNumberAndStatus(int $balconyNumber, TicketStatus $ticketStatus): array
    {
        $query = '
            select * from tickets t
                join services s
                on t.id = s.ticket_id
            where s.balcony_number = :balcony_number and t.status = :ticket_status;
        ';

        $statment = $this->connection->prepare($query);
        $statment->bindParam(':ticket_status', $ticketStatus);
        $statment->bindParam(':balcony_number', $balconyNumber);
        $statment->execute();

        $service = $statment->fetch(PDO::FETCH_ASSOC);

        if ($statment->rowCount() === 0) return [];
        return $service;
    }

    public function readTicketsByStatus(TicketStatus $ticketStatus): array
    {
        $query = '
            select * from tickets where status = :status;
        ';

        $statment = $this->connection->prepare($query);
        $statment->bindParam(':status', $ticketStatus);
        $statment->execute();

        $tickets = $statment->fetch(PDO::FETCH_ASSOC);
        if ($statment->rowCount() === 0) return [];
        return $tickets;
    }
}