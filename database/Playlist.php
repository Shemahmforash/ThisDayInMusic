<?php
use Doctrine\Common\Collections\ArrayCollection;

// src/Playlist.php
/**
 * @Entity @Table(name="Playlist",indexes={@index(name="date_idx", columns={"date"})})
 */
class Playlist {

    /**
     * @var int
     */
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
    * @ManyToMany(targetEntity="Track")
    **/
    protected $tracks;

    /**
     * @var int
     */
    /** @Column(type="date") **/
    protected $date;

    public function __construct() {
        $this->tracks = new ArrayCollection();
    }

    public function addTrack( Track $track ) {
        $this->tracks[] = $track;
    }

    /*Getters and setters*/
    public function getId() {
        return $this->id;
    }

    public function getTracks() {
        return $this->tracks;
    }

    public function getDate() {
        return $this->date;
    }

    public function setDate(DateTime $date) {
        $this->date = $date;
    }
}

?>
