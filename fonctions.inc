<?php
    function messageErreur ($erreur) {
        echo "
            <h1 class='erreur'>
                Error
            </h1>
            <p class='texte-erreur'>
                ".$erreur."
            </p>
        ";
    }
?>