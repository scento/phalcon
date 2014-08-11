#Access Control Lists

##Kurzbeschreibung
Zugriffskontrolllisten (*Access Control Lists*) ermöglichen eine abstrahierte Verwaltung von Rollen,
Ressourcen und Berechtigungen. Ein *ACL-Adapter* verwaltet Rollen und Ressourcen, verknüpft diese mit
Berechtigungen und speichert diese adapterspezifisch. Das Basisprojekt von Phalcon implementiert nur
den `Memory`-Adapter, welcher die Zugriffskontrolliste als PHP-Objekt im Zwischenspeicher für eine 
Anfrage vorhält.

##Codebeispiele

**Allgemeine Nutzung**
```php
	$acl = new Phalcon\Acl\Adapter\Memory();
	
	//Standardmäßig Zugriff auf Ressourcen verhindern
	$acl->setDefaultAction(Phalcon\Acl::DENY);
	
	/* Rollen */
	
	//Rollen mit optionaler Beschreibung erstellen
	$role_admin = new Phalcon\Acl\Role('Administrators', 'root access');
	$role_user = new Phalcon\Acl\Role('Users');
	
	//Rollen zur ACL hinzufügen
	$acl->addRole($role_admin);
	$acl->addRole($role_user);
	
	//Rollen direkt erstellen
	$acl->addRole('Guests');
	
	/* Ressourcen */
	
	//Ressource mit optionaler Beschreibung erstellen
	$resource_blog = new Phalcon\Acl\Resource('Blog', 'It is like news.');
	
	//Ressource mit Aktionen zur ACL hinzufügen
	$acl->addResource($resource_blog, array('list', 'show', 'create', 'edit', 'delete'));
	
	//Ressource direkt hinzufügen
	$acl->addResource('Legal', array('imprint', 'privacy');
	
	/* Zugriffe */
	
	//Allgemeiner Zugriff auf alle 'Legal'-Ressourcen
	$acl->allow('Administrators', 'Legal', '*');
	$acl->allow('Users', 'Legal', '*');
	$acl->allow('Guests', 'Legal', '*');
	
	//'Users 'dürfen auf einzelne Aktionen zugreifen
	$acl->allow('Users', 'Blog', array('list', 'show'));
	
	//'Administrators' haben Vollzugriff
	$acl->allow('Administrators', '*', '*');
	
	/* Abfrage */
	
	//Dürfen 'Administrators' auf 'Legal::imprint' zugreifen?
	if($acl->isAllowed('Administrators', 'Legal', 'imprint') == true) {
		echo 'Zugriff gestattet.';
	} else {
		echo 'Zugriff verweigert.';
	}
```

##Bekannte Probleme
* Phalcon 0.5.3
	- [Fehler bei der Vererbung von Zugriffsrechten](https://github.com/phalcon/cphalcon/issues/65)
* Phalcon 1.0.0
	- [Illegal instruction in `Acl\Memory`](https://github.com/phalcon/cphalcon/commit/54085524876b17eca46083ac833c871a1d7b92c6)
* Phalcon 1.2.0
	- [Fehler bei Wildcard-Nutzung](https://github.com/phalcon/cphalcon/issues/759)
* Phalcon 1.2.4
	- [Segmentation Fault durch `dropResourceAccess`](https://github.com/phalcon/cphalcon/issues/1376)
	- [Zugriff auf gesperrte Ressourcen möglich](https://github.com/phalcon/cphalcon/issues/1303)
	- [Null-Pointer in `dropResourceAccess`](https://github.com/phalcon/cphalcon/commit/218af45edf327882efbff04b4acd1dd490ba0dfb)
* Phalcon 1.3.0
	- [Numerische Ressourcen sind unzulässig](https://github.com/phalcon/cphalcon/issues/1890)
* Phalcon 1.3.2
	- [Interner Fehler bei Variablenerzeugung](https://github.com/phalcon/cphalcon/pull/2421)
	- [Interner Fehler bei Variablenerzeugung](https://github.com/phalcon/cphalcon/issues/1258)
	- [Fehlerhafte Rollenvererbung](http://forum.phalconphp.com/discussion/103/acl-roles-inheritance)
	- [Segmentation Fault durch `role_adapter_memory_check_inheritance`](https://github.com/phalcon/cphalcon/commit/d7a92ebba53098865d7bfd1179d48e4ef456feb6)
	- [Speicherleck in `role_adapter_memory_check_inheritance`](https://github.com/phalcon/cphalcon/commit/005971b9556a7c799eb65582d6667f570682d592)
* Phalcon 2.0.0
	- [Verwendung von booleschen Werten statt numerischen für die Zugriffskonstanten](https://github.com/phalcon/cphalcon/pull/2500)
* Entwicklungsversion
	- [Wildcard-Fehler bei Rollen](https://github.com/phalcon/cphalcon/issues/2648)

##Incubator
Das [Incubator-Projekt](https://github.com/phalcon/incubator) erweitert die Zugriffskontrollisten von 
Phalcon in zwei Bereichen. Einerseits werden weitere Adapter zur Verfügung gestellt, andererseits wird
der Umgang mit dem bestehenden `Memory`-Adapter vereinfacht.

###Adapter
Es werden zwei Adapter zur Verfügung gestellt - 
[`Database`](https://github.com/phalcon/incubator/tree/master/Library/Phalcon/Acl/Adapter)
und [`Mongo`](https://github.com/phalcon/incubator/tree/master/Library/Phalcon/Acl/Adapter).
Der `Database`-Adapter nutzt die Datenbankabstraktion von Phalcon zur Speicherung von *ACL*, der
`Mongo`-Adapter ermöglicht dies mit dem nichtrelationalen Datenbanksystem [MongoDB](http://www.mongodb.org/).
Details zur Integrierung und Implementierung der Adapter finden sich auf der dazugehörigen 
[Projektseite](https://github.com/phalcon/incubator/tree/master/Library/Phalcon/Acl/Adapter).

###Factory
Die [`Factory`](https://github.com/phalcon/incubator/tree/master/Library/Phalcon/Acl/Factory)-Erweiterung
ermöglicht die Definition von *ACL-Regeln* mit den `Config`-Klassen von Phalcon. Auf diese Weise soll
die Einrichtung und Nutzung des `Memory`-Adapters vereinfacht werden. Details zur Integrierung und
Implementierung der Adapter finden sich auf der dazugehörigen 
[Projektseite](https://github.com/phalcon/incubator/tree/master/Library/Phalcon/Acl/Factory).

##Namespaces, Klassen und Interfaces
###Übersicht
![Allgemeine UML-Klassenübersicht](https://rawgit.com/scento/phalcon-php/master/doc/assets/Acl/Overview_Class.svg)

Sämtliche *ACL*-bezogenen Klassen und Interfaces befinden sich im oder in Subräumen des Namensraumes `Phalcon\Acl`.
Die einzige Ausnahme ist die abstrakte Klasse `Acl` im Namensraum `Phalcon`.

**Klassen**
* `Phalcon\Acl`
* `Phalcon\Acl\Adapter`
* `Phalcon\Acl\Exception`
* `Phalcon\Acl\Resource`
* `Phalcon\Acl\Role`
* `Phalcon\Acl\Adapter\Memory`

**Interfaces**
* `Phalcon\Acl\AdapterInterface`
* `Phalcon\Acl\ResourceInterface`
* `Phalcon\Acl\RoleInterface`

###Klassen
![UML-Klassenübersicht](https://rawgit.com/scento/phalcon-php/master/doc/assets/Acl/Overview_Class2.svg)