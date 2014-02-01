<?php
// src/Track.php
/**
 * @Entity @Table(name="Track")
**/
class Track {
    /**
     * @var int
     */
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
     * @var string
     */
    /** @Column(type="string", nullable=true) **/
    protected $name;

    /**
     * @ManyToOne(targetEntity="Artist", inversedBy="tracks")
     **/ 
    protected $artist;

    public function getArtist() {
        return $this->artist;
    }

    /*Getters and setters*/
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }
}
?>
