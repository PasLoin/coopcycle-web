<?php

namespace AppBundle\Entity\Incident;

use ApiPlatform\Core\Annotation\ApiResource;
use AppBundle\Entity\Model\TaggableInterface;
use AppBundle\Entity\Model\TaggableTrait;
use AppBundle\Entity\Task;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use AppBundle\Action\Incident\CreateComment;
use AppBundle\Action\Incident\IncidentAction;
use AppBundle\Action\Incident\IncidentFastList;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ApiResource(
 *   collectionOperations={
 *     "get"={
 *       "method"="GET",
 *       "controller"=IncidentFastList::class,
 *     },
 *     "post"={
 *       "method"="POST",
 *     }
 *   },
 *   itemOperations={
 *     "get"={
 *       "method"="GET",
 *     },
 *     "patch"={
 *       "method"="PATCH",
 *     },
 *     "put"={
 *       "method"="PUT",
 *     },
 *     "add_comment"={
 *       "method"="POST",
 *       "path"="/incidents/{id}/comments",
 *       "controller"=CreateComment::class,
 *     },
 *     "action"={
 *       "method"="PUT",
 *       "path"="/incidents/{id}/action",
 *       "controller"=IncidentAction::class,
 *     }
 *   }
 * )
 */
class Incident implements TaggableInterface {
    use TaggableTrait;

    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected int $id;

    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected string $title;


    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected string $status;


    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected int $priority;


    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected Task $task;


    /**
    * @Groups({"incident"})
    */
    protected ?string $failure_reason_code = null;


    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected ?string $description = null;


    /**
    * @Groups({"incident"})
    */
    protected Collection $images;


    /**
    * @Groups({"incident"})
    */
    protected Collection $events;


    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected ?User $createdBy = null;


    /**
    * @Groups({"incident", "incident_get_collection"})
    */
    protected $createdAt;


    /**
    * @Groups({"incident"})
    */
    protected $updatedAt;

    const STATUS_OPEN = 'OPEN';
    const STATUS_CLOSED = 'CLOSED';
    const STATUS_RESOLVED = 'RESOLVED';

    const PRIORITY_HIGH = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_LOW = 3;


    public function __construct() {
        $this->images = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): self {
        $this->title = $title;
        return $this;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function setStatus(string $status): self {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): int {
        return $this->priority;
    }

    public function setPriority(int $priority): self {
        $this->priority = $priority;
        return $this;
    }

    public function getTask(): Task {
        return $this->task;
    }

    public function setTask(Task $task): self {
        $this->task = $task;
        return $this;
    }

    public function getFailureReasonCode(): string {
        return $this->failure_reason_code;
    }

    public function setFailureReasonCode(string $failure_reason_code): self {
        $this->failure_reason_code = $failure_reason_code;
        return $this;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): self {
        $this->description = $description;
        return $this;
    }

    public function getImages(): Collection {
        return $this->images;
    }

    public function addImage(IncidentImage $image): self {
        $this->images[] = $image;
        return $this;
    }

    public function getEvents(): Collection {
        return $this->events;
    }

    public function addEvent(IncidentEvent $event): self {
        $this->events[] = $event;
        return $this;
    }

    public function getCreatedBy(): ?User {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $created_by): self {
        $this->createdBy = $created_by;
        return $this;
    }

    public function getCreatedAt(): mixed {
        return $this->createdAt;
    }

    public function getUpdatedAt(): mixed {
        return $this->updatedAt;
    }

    public function getCustomerUserInfo(): ?User {
        return $this->getTask()->getDelivery()?->getOrder()?->getCustomer()?->getUser();
    }

}
