<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <title>Campus IT</title>
    <link rel="stylesheet" type="text/css" href="style.css" />
  </head>
  <body>
  <?php
      // PARTIE DONNEES ---------------------------------------------------------

      // inclusion de la méthode de dialogue avec la BD
      require_once 'persistance/dialogueBD.php';

      try {
          // on crée un objet référençant la classe DialogueBD
          $undlg = new DialogueBD();
          $tabAppConso = $undlg->getConsommationApplications();
          $tabEvoMensu = $undlg->getEvolutionMensuelle();
          $tabComparaison = $undlg->getComparaison();
      } catch (Exception $e) {
          $erreur = $e->getMessage();
          messageErreur($erreur);
      }
  ?>
    <div class="tab">
      <button class="tablinks" onclick="openTab(event, 'Tab1')">Top applications</button>
      <button class="tablinks" onclick="openTab(event, 'Tab2')">Évolution mensuelle</button>
      <button class="tablinks" onclick="openTab(event, 'Tab3')">Comparaison ressources</button>
    </div>

    <div id="Tab1" class="tabcontent">
      <h3>Top 5 des applications (consommation totale)</h3>
      <table>
          <thead>
            <tr>
                <th>
                    Applications
                </th>
                <th>
                    Total (unités cumulées)
                </th>
            </tr>
          </thead>
          <tbody>
              <?php
                foreach($tabAppConso as $ligne) {
                    echo "
                        <tr>
                            <th> ".$ligne["nom"]."</th>
                            <th> ".$ligne["volume"]."</th>
                        </tr>
                    ";
                }
              ?>
          </tbody>
      </table>
    </div>

    <div id="Tab2" class="tabcontent">
      <h3>Évolution mensuelle (total campus)</h3>
        <table>
            <thead>
            <tr>
                <th>
                    Mois
                </th>
                <th>
                    Volume
                </th>
            </tr>
            </thead>
            <tbody>
                <?php
                    foreach($tabEvoMensu as $ligne) {
                        echo "
                                <tr>
                                    <th> ".$ligne["mois"]."</th>
                                    <th> ".$ligne["volume"]."</th>
                                </tr>
                            ";
                    }
                ?>
            </tbody>
        </table>
    </div>

    <div id="Tab3" class="tabcontent">
      <h3>Comparaison Stockage vs Réseau</h3>
        <table>
            <thead>
            <tr>
                <th>
                    Mois
                </th>
                <th>
                    Stockage
                </th>
                <th>
                    Réseau
                </th>
            </tr>
            </thead>
            <tbody>
                <?php
                    foreach($tabComparaison as $ligne) {
                        echo "
                                <tr>
                                    <th> ".$ligne["mois"]."</th>
                                    <th> ".$ligne["stockage"]."</th>
                                    <th> ".$ligne["reseau"]."</th>
                                </tr>
                            ";
                    }
                ?>
            </tbody>
        </table>
    </div>

    <script src="script.js"></script>
  </body>
</html>
