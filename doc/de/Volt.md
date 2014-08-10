#Volt

##Kurzbeschreibung
Volt ist eine durch [Jinja](http://jinja.pocoo.org/) beeinflusste Sprache für *View-Templates* und 
intern ein *Engine-Adapter* für die *View*-Komponente des *Model-View-Controller*-Systems von 
Phalcon. Volt wird - ähnlich wie PHP - in HTML-Dokumente eingebettet und lässt sich aufgrund dieser 
Eigenschaft nahtlos in PHP konvertieren.
Um dies zu erreichen, werden die mit Volt angereicherten HTML-Templates in einem mehrstufigen 
Verfahren in PHP-Code umgewandelt, welcher zur Geschwindigkeitsoptimierung zwischengespeichert wird.

##Kompilierung
![Übersicht zum Kompilierungsvorgang](https://rawgit.com/scento/phalcon-php/master/doc/assets/Volt/Overview_Compiler.svg)

Wenn ein *Volt-Template* neu übersetzt werden muss, werden die folgenden Schritte bis zur 
Generierung des PHP-Codes durchgeführt:

* **Scanner** Der Scanner liest die Zeichenkette ein, entfernt Kommentare und trennt den *String*
an *Tokens*. Diese einzelnen Abschnitte der Zeichenkette werden anschließend weiterverarbeitet.
* **Tokenizer** Im Tokenizer werden die durch den *Scanner* markierten *Tokens* und *Inhalte*
gruppiert, bzw. extrahiert. Der *Tokenizer* generiert eine *Intermediate*-Darstellung, welche
den Volt-Code als Baumstruktur aus *Tokens* mit Inhalten zurückgibt.
* **Compiler** Der Compiler verarbeitet die von *Tokenizer* generierte Baumstruktur zu gültigem
PHP-Code und übergibt diese Daten entweder an die *View-Komponente* oder speichert die generierten
Daten als Datei, sodass der Volt-Code nicht erneut kompiliert werden muss.

##Namespaces, Klassen und Interfaces
###Übersicht
![Allgemeine UML-Klassenübersicht](https://rawgit.com/scento/phalcon-php/master/doc/assets/Volt/Overview_Class.svg)

Sämtliche Volt-bezogenen Klassen (mit Ausnahme der *View*-Integration) finden sich in den Namensräumen
`Phalcon\Mvc\View\Engine` und `Phalcon\Mvc\View\Engine\Volt`.

###Klassen
![UML-Klassenübersicht](https://rawgit.com/scento/phalcon-php/master/doc/assets/Volt/Overview_Class2.svg)