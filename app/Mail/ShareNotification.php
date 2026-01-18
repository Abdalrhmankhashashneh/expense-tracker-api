<?php

namespace App\Mail;

use App\Models\ShareToken;
use App\Models\User;
use App\Models\Debt;
use App\Models\Lending;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ShareToken $shareToken;
    public User $sender;
    public string $shareUrl;
    public string $resourceType;
    public string $personName;
    public float $amount;
    public float $remainingAmount;

    /**
     * Create a new message instance.
     */
    public function __construct(ShareToken $shareToken, User $sender)
    {
        $this->shareToken = $shareToken;
        $this->sender = $sender;

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
        $this->shareUrl = rtrim($frontendUrl, '/') . '/shared/' . $shareToken->token;

        $resource = $shareToken->shareable;

        if ($resource instanceof Debt) {
            $this->resourceType = 'debt';
            $this->personName = $resource->debtor_name;
            $this->amount = (float) $resource->total_amount;
            $this->remainingAmount = $resource->remaining_amount;
        } elseif ($resource instanceof Lending) {
            $this->resourceType = 'lending';
            $this->personName = $resource->borrower_name;
            $this->amount = (float) $resource->amount;
            $this->remainingAmount = (float) $resource->remaining_amount;
        } else {
            $this->resourceType = 'unknown';
            $this->personName = 'Unknown';
            $this->amount = 0;
            $this->remainingAmount = 0;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->resourceType === 'debt'
            ? "{$this->sender->name} shared a debt record with you"
            : "{$this->sender->name} shared a lending record with you";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.share-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
