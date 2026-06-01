<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Cie10Seeder extends Seeder
{
    public function run()
    {
        $codes = [];

        DB::table('cie10_codes')->truncate();


        // CAPÍTULO I: Ciertas enfermedades infecciosas y parasitarias
        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A00','Cólera',[['A00.0','Cólera debido a Vibrio cholerae'],['A00.1','Cólera debido a Vibrio cholerae El Tor'],['A00.9','Cólera no especificado']]],
            ['A01','Fiebres tifoidea y paratifoidea'],
            ['A02','Otras infecciones debidas a Salmonella'],
            ['A03','Shigelosis'],
            ['A04','Otras infecciones bacterianas intestinales'],
            ['A05','Otras intoxicaciones alimentarias bacterianas'],
            ['A06','Amebiasis'],
            ['A07','Otras enfermedades intestinales por protozoarios'],
            ['A08','Infecciones intestinales virales'],
            ['A09','Diarrea y gastroenteritis presunto origen infeccioso',[['A09.0','Diarrea y gastroenteritis presunto origen infeccioso'],['A09.9','Diarrea y gastroenteritis de origen no especificado']]],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A15','Tuberculosis respiratoria confirmada',[['A15.0','TB pulmonar confirmada microscopía'],['A15.1','TB pulmonar confirmada cultivo'],['A15.2','TB pulmonar confirmada histología'],['A15.3','TB pulmonar confirmada medios no especificados'],['A15.4','TB ganglios intratorácicos confirmada'],['A15.5','TB laringe tráquea bronquios confirmada'],['A15.6','TB pleura confirmada'],['A15.7','TB respiratoria primaria confirmada'],['A15.8','TB respiratoria otras confirmadas'],['A15.9','TB respiratoria no especificada confirmada']]],
            ['A16','Tuberculosis respiratoria no confirmada',[['A16.0','TB pulmonar sin confirmación'],['A16.1','TB pulmonar sin confirmación bacteriológica'],['A16.2','TB pulmonar sin mención confirmación'],['A16.3','TB ganglios intratorácicos sin confirmación'],['A16.4','TB laringe tráquea bronquios sin confirmación'],['A16.5','TB pleura sin confirmación'],['A16.7','TB respiratoria primaria sin confirmación'],['A16.8','TB respiratoria otras sin confirmación'],['A16.9','TB respiratoria no especificada sin confirmación']]],
            ['A17','Tuberculosis del sistema nervioso'],
            ['A18','Tuberculosis de otros órganos'],
            ['A19','Tuberculosis miliar'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A20','Peste'],['A21','Tularemia'],['A22','Ántrax'],['A23','Brucelosis'],
            ['A24','Muermo y melioidosis'],['A25','Fiebres por mordedura de rata'],['A26','Erisipeloide'],
            ['A27','Leptospirosis'],['A28','Otras zoonóticas bacterianas'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A30','Lepra'],['A31','Otras micobacterias'],['A32','Listeriosis'],['A33','Tétanos neonatal'],
            ['A34','Tétanos obstétrico'],['A35','Otros tétanos'],['A36','Difteria'],['A37','Tos ferina'],
            ['A38','Escarlatina'],['A39','Infección meningocócica'],['A40','Septicemia estreptocócica'],
            ['A41','Otras septicemias'],['A42','Actinomicosis'],['A43','Nocardiosis'],['A44','Bartonelosis'],
            ['A46','Erisipela'],['A48','Otras bacterianas no clasificadas'],['A49','Infección bacteriana sitio no especificado'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A50','Sífilis congénita'],['A51','Sífilis precoz'],['A52','Sífilis tardía'],
            ['A53','Otras sífilis'],['A54','Infección gonocócica',[['A54.0','Gonococia tracto genitourinario inferior'],['A54.1','Gonococia tracto genitourinario superior'],['A54.2','Gonococia diseminada'],['A54.3','Infección gonocócica del ojo'],['A54.4','Gonococia sistema musculoesquelético'],['A54.5','Faringitis gonocócica'],['A54.6','Infección gonocócica ano y recto'],['A54.8','Otras infecciones gonocócicas'],['A54.9','Infección gonocócica no especificada']]],
            ['A55','Linfogranuloma venéreo'],['A56','Clamidias ETS'],['A57','Chancro blando'],
            ['A58','Granuloma inguinal'],['A59','Tricomoniasis'],
            ['A60','Herpes anogenital',[['A60.0','Herpes genital'],['A60.1','Herpes perianal y rectal'],['A60.9','Herpes anogenital no especificado']]],
            ['A63','Otras ETS no clasificadas'],['A64','ETS no especificada'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A65','Sífilis no venérea'],['A66','Frambesia'],['A67','Pinta'],['A68','Fiebres recurrentes'],['A69','Otras espiroquetosis'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A70','Psittacosis'],['A71','Tracoma'],['A74','Otras clamidias'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A75','Tifus'],['A77','Fiebre maculosa'],['A78','Fiebre Q'],['A79','Otras rickettsiosis'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A80','Poliomielitis aguda'],['A81','Infecciones por virus lentos SNC'],['A82','Rabia'],
            ['A83','Encefalitis por mosquitos'],['A84','Encefalitis por garrapatas'],
            ['A85','Otras encefalitis virales'],['A86','Encefalitis viral no especificada'],
            ['A87','Meningitis viral'],['A88','Otras infecciones virales SNC'],['A89','Infección viral SNC no especificada'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['A90','Dengue'],['A91','Dengue hemorrágico'],['A92','Otras fiebres por mosquitos'],
            ['A93','Otras fiebres por artrópodos'],['A94','Fiebre por artrópodos no especificada'],
            ['A95','Fiebre amarilla'],['A96','Fiebre hemorrágica por arenavirus'],
            ['A98','Otras fiebres hemorrágicas virales'],['A99','Fiebre hemorrágica viral no especificada'],
        ]);


        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B00','Herpes viral',[['B00.0','Eczema herpético'],['B00.1','Dermatitis vesicular herpética'],['B00.2','Gingivoestomatitis herpética'],['B00.3','Meningitis herpética'],['B00.4','Encefalitis herpética'],['B00.5','Enfermedad ocular herpética'],['B00.7','Herpes diseminado'],['B00.8','Otras infecciones herpéticas'],['B00.9','Infección herpética no especificada']]],
            ['B01','Varicela',[['B01.0','Meningitis varicelosa'],['B01.1','Encefalitis varicelosa'],['B01.2','Neumonía varicelosa'],['B01.8','Varicela con otras complicaciones'],['B01.9','Varicela sin complicaciones']]],
            ['B02','Herpes zóster',[['B02.0','Encefalitis por herpes zóster'],['B02.1','Meningitis por herpes zóster'],['B02.2','Herpes zóster con otras complicaciones SNC'],['B02.3','Herpes zóster ocular'],['B02.7','Herpes zóster diseminado'],['B02.8','Herpes zóster con otras complicaciones'],['B02.9','Herpes zóster sin complicaciones']]],
            ['B03','Viruela'],['B04','Viruela de los monos'],
            ['B05','Sarampión',[['B05.0','Sarampión con encefalitis'],['B05.1','Sarampión con meningitis'],['B05.2','Sarampión con neumonía'],['B05.3','Sarampión con otitis media'],['B05.4','Sarampión con complicaciones intestinales'],['B05.8','Sarampión con otras complicaciones'],['B05.9','Sarampión sin complicaciones']]],
            ['B06','Rubéola',[['B06.0','Rubéola con complicaciones neurológicas'],['B06.8','Rubéola con otras complicaciones'],['B06.9','Rubéola sin complicaciones']]],
            ['B07','Verrugas virales'],['B08','Otras virales cutáneas'],['B09','Viral cutánea no especificada'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B15','Hepatitis A',[['B15.0','Hepatitis A con coma hepático'],['B15.9','Hepatitis A sin coma hepático']]],
            ['B16','Hepatitis B',[['B16.0','Hepatitis B aguda con delta y coma'],['B16.1','Hepatitis B aguda con delta sin coma'],['B16.2','Hepatitis B aguda sin delta con coma'],['B16.9','Hepatitis B aguda sin delta sin coma']]],
            ['B17','Otras hepatitis agudas',[['B17.0','Hepatitis delta aguda'],['B17.1','Hepatitis C aguda'],['B17.2','Hepatitis E aguda'],['B17.8','Otras hepatitis virales agudas'],['B17.9','Hepatitis viral aguda no especificada']]],
            ['B18','Hepatitis viral crónica',[['B18.0','Hepatitis B crónica con delta'],['B18.1','Hepatitis B crónica sin delta'],['B18.2','Hepatitis C crónica'],['B18.8','Otras hepatitis virales crónicas'],['B18.9','Hepatitis viral crónica no especificada']]],
            ['B19','Hepatitis viral no especificada',[['B19.0','Hepatitis viral no especificada con coma'],['B19.9','Hepatitis viral no especificada sin coma']]],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B20','VIH resultante en enfermedades infecciosas y parasitarias'],
            ['B21','VIH resultante en tumores malignos'],
            ['B22','VIH resultante en otras enfermedades especificadas'],
            ['B23','VIH resultante en otras afecciones'],
            ['B24','VIH/SIDA no especificado'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B25','Citomegalovirus'],['B26','Parotiditis infecciosa'],['B27','Mononucleosis infecciosa'],
            ['B30','Conjuntivitis viral'],['B33','Otras virales no clasificadas'],['B34','Viral sitio no especificado'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B35','Dermatofitosis',[['B35.0','Tiña de la barba y cuero cabelludo'],['B35.1','Tiña de las uñas'],['B35.2','Tiña de la mano'],['B35.3','Tiña del pie'],['B35.4','Tiña del cuerpo'],['B35.5','Tiña imbricada'],['B35.6','Tiña inguinal'],['B35.8','Otras dermatofitosis'],['B35.9','Dermatofitosis no especificada']]],
            ['B36','Otras micosis superficiales'],
            ['B37','Candidiasis',[['B37.0','Estomatitis candidiásica'],['B37.1','Candidiasis pulmonar'],['B37.2','Candidiasis uña y piel'],['B37.3','Candidiasis vulvovaginal'],['B37.4','Candidiasis otras urogenitales'],['B37.5','Meningitis candidiásica'],['B37.6','Endocarditis candidiásica'],['B37.7','Septicemia candidiásica'],['B37.8','Candidiasis otros sitios'],['B37.9','Candidiasis no especificada']]],
            ['B38','Coccidioidomicosis'],['B39','Histoplasmosis'],['B40','Blastomicosis'],
            ['B41','Paracoccidioidomicosis'],['B42','Esporotricosis'],['B43','Cromomicosis'],
            ['B44','Aspergilosis'],['B45','Criptococosis'],['B46','Cigomicosis'],
            ['B47','Micetoma'],['B48','Otras micosis'],['B49','Micosis no especificada'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B50','Paludismo falciparum',[['B50.0','Paludismo falciparum con complicaciones cerebrales'],['B50.8','Otras formas graves de paludismo falciparum'],['B50.9','Paludismo falciparum no especificado']]],
            ['B51','Paludismo vivax',[['B51.0','Paludismo vivax con complicaciones'],['B51.8','Paludismo vivax con otras complicaciones'],['B51.9','Paludismo vivax sin complicaciones']]],
            ['B52','Paludismo malariae',[['B52.0','Paludismo malariae con complicaciones'],['B52.8','Paludismo malariae con otras complicaciones'],['B52.9','Paludismo malariae sin complicaciones']]],
            ['B53','Otras formas de paludismo'],['B54','Paludismo no especificado'],
            ['B55','Leishmaniasis'],['B56','Tripanosomiasis africana'],['B57','Enfermedad de Chagas'],
            ['B58','Toxoplasmosis'],['B59','Neumocistosis'],['B60','Otras protozoarias'],['B64','Protozoaria no especificada'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B65','Esquistosomiasis'],['B66','Otras trematodosis'],['B67','Equinococosis'],
            ['B68','Teniasis'],['B69','Cisticercosis'],['B70','Difilobotriasis'],['B71','Otras cestodosis'],
            ['B72','Dracontiasis'],['B73','Oncocercosis'],['B74','Filariasis'],['B75','Triquinosis'],
            ['B76','Anquilostomiasis'],['B77','Ascaridiasis'],['B78','Estrongiloidiasis'],['B79','Tricuriasis'],
            ['B80','Enterobiasis'],['B81','Otras helmintiasis intestinales'],['B82','Helmintiasis intestinal no especificada'],
            ['B83','Otras helmintiasis'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B85','Pediculosis'],['B86','Escabiosis'],['B87','Miasis'],['B88','Otras infestaciones'],
            ['B89','Enfermedad parasitaria no especificada'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B90','Secuelas de tuberculosis'],['B91','Secuelas de poliomielitis'],['B92','Secuelas de lepra'],
            ['B94','Secuelas de otras enfermedades infecciosas'],
        ]);

        $this->addCodes($codes, 'I', 'Ciertas enfermedades infecciosas y parasitarias', [
            ['B95','Estreptococos/estafilococos como causa'],['B96','Otros agentes bacterianos como causa'],
            ['B97','Agentes virales como causa'],['B98','Otros agentes infecciosos'],['B99','Otras enfermedades infecciosas'],
        ]);


        // CAPÍTULO II: Neoplasias
        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C00','Tumor maligno del labio'],['C01','Tumor maligno de la base de la lengua'],
            ['C02','Tumor maligno de otras partes y no especificadas de la lengua'],['C03','Tumor maligno de la encía'],
            ['C04','Tumor maligno del piso de la boca'],['C05','Tumor maligno del paladar'],
            ['C06','Tumor maligno de otras partes y no especificadas de la boca'],['C07','Tumor maligno de la glándula parótida'],
            ['C08','Tumor maligno de otras glándulas salivales mayores'],['C09','Tumor maligno de la amígdala'],
            ['C10','Tumor maligno de la orofaringe'],['C11','Tumor maligno de la nasofaringe'],
            ['C12','Tumor maligno del seno piriforme'],['C13','Tumor maligno de la hipofaringe'],
            ['C14','Tumor maligno de otros sitios y mal definidos del labio cavidad oral y faringe'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C15','Tumor maligno del esófago'],
            ['C16','Tumor maligno del estómago',[['C16.0','Cardias'],['C16.1','Fondo'],['C16.2','Cuerpo'],['C16.3','Antro'],['C16.4','Píloro'],['C16.5','Curvatura menor'],['C16.6','Curvatura mayor'],['C16.8','Lesión sobremontante'],['C16.9','Estómago no especificado']]],
            ['C17','Tumor maligno del intestino delgado'],
            ['C18','Tumor maligno del colon',[['C18.0','Ciego'],['C18.1','Apéndice'],['C18.2','Colon ascendente'],['C18.3','Flexura hepática'],['C18.4','Colon transverso'],['C18.5','Flexura esplénica'],['C18.6','Colon descendente'],['C18.7','Colon sigmoide'],['C18.8','Lesión sobremontante'],['C18.9','Colon no especificado']]],
            ['C19','Tumor maligno de la unión rectosigmoidea'],['C20','Tumor maligno del recto'],
            ['C21','Tumor maligno del ano y conducto anal'],
            ['C22','Tumor maligno del hígado y vías biliares intrahepáticas',[['C22.0','Carcinoma hepatocelular'],['C22.1','Carcinoma vías biliares intrahepáticas'],['C22.2','Hepatoblastoma'],['C22.3','Angiosarcoma hepático'],['C22.4','Otros sarcomas hepáticos'],['C22.7','Otros carcinomas hepáticos'],['C22.9','Hígado no especificado']]],
            ['C23','Tumor maligno de la vesícula biliar'],
            ['C24','Tumor maligno de otras partes de las vías biliares'],
            ['C25','Tumor maligno del páncreas',[['C25.0','Cabeza'],['C25.1','Cuerpo'],['C25.2','Cola'],['C25.3','Conducto pancreático'],['C25.4','Endocrino'],['C25.7','Otras partes'],['C25.8','Lesión sobremontante'],['C25.9','Páncreas no especificado']]],
            ['C26','Tumor maligno de otros sitios y mal definidos del sistema digestivo'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C30','Tumor maligno de las fosas nasales y oído medio'],['C31','Tumor maligno de los senos paranasales'],
            ['C32','Tumor maligno de la laringe'],['C33','Tumor maligno de la tráquea'],
            ['C34','Tumor maligno de los bronquios y pulmón',[['C34.0','Bronquio principal'],['C34.1','Lóbulo superior'],['C34.2','Lóbulo medio'],['C34.3','Lóbulo inferior'],['C34.8','Lesión sobremontante'],['C34.9','Bronquio o pulmón no especificado']]],
            ['C37','Tumor maligno del timo'],['C38','Tumor maligno del corazón mediastino y pleura'],
            ['C39','Tumor maligno de otros sitios del sistema respiratorio'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C40','Tumor maligno de huesos y cartílagos de los miembros'],
            ['C41','Tumor maligno de huesos y cartílagos de otros sitios'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C43','Melanoma maligno de la piel',[['C43.0','Melanoma del labio'],['C43.1','Melanoma del párpado'],['C43.2','Melanoma de la oreja'],['C43.3','Melanoma de la cara'],['C43.4','Melanoma del cuero cabelludo'],['C43.5','Melanoma del tronco'],['C43.6','Melanoma del miembro superior'],['C43.7','Melanoma del miembro inferior'],['C43.8','Melanoma múltiple'],['C43.9','Melanoma no especificado']]],
            ['C44','Otros tumores malignos de la piel',[['C44.0','Labio'],['C44.1','Párpado'],['C44.2','Oreja'],['C44.3','Cara'],['C44.4','Cuero cabelludo'],['C44.5','Tronco'],['C44.6','Miembro superior'],['C44.7','Miembro inferior'],['C44.8','Lesión múltiple'],['C44.9','Piel no especificada']]],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C45','Mesotelioma'],['C46','Sarcoma de Kaposi'],
            ['C47','Tumor maligno de nervios periféricos y SNA'],['C48','Tumor maligno del peritoneo y retroperitoneo'],
            ['C49','Tumor maligno de otros tejidos conjuntivos y blandos'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C50','Tumor maligno de la mama',[['C50.0','Pezón'],['C50.1','Porción central'],['C50.2','Cuadrante superior interno'],['C50.3','Cuadrante inferior interno'],['C50.4','Cuadrante superior externo'],['C50.5','Cuadrante inferior externo'],['C50.6','Axila'],['C50.8','Lesión múltiple'],['C50.9','Mama no especificada']]],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C51','Tumor maligno de la vulva'],['C52','Tumor maligno de la vagina'],
            ['C53','Tumor maligno del cuello del útero',[['C53.0','Endocérvix'],['C53.1','Exocérvix'],['C53.8','Lesión sobremontante'],['C53.9','Cuello uterino no especificado']]],
            ['C54','Tumor maligno del cuerpo del útero',[['C54.0','Istmo'],['C54.1','Endometrio'],['C54.2','Miometrio'],['C54.3','Fondo'],['C54.8','Lesión sobremontante'],['C54.9','Cuerpo uterino no especificado']]],
            ['C55','Tumor maligno del útero parte no especificada'],['C56','Tumor maligno del ovario'],
            ['C57','Tumor maligno de otros órganos genitales femeninos'],['C58','Tumor maligno de la placenta'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C60','Tumor maligno del pene'],
            ['C61','Tumor maligno de la próstata',[['C61.0','Próstata'],['C61.9','Próstata no especificado']]],
            ['C62','Tumor maligno del testículo'],['C63','Tumor maligno de otros órganos genitales masculinos'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C64','Tumor maligno del riñón'],['C65','Tumor maligno de la pelvis renal'],
            ['C66','Tumor maligno del uréter'],['C67','Tumor maligno de la vejiga urinaria'],
            ['C68','Tumor maligno de otros órganos urinarios'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C69','Tumor maligno del ojo y sus anexos'],['C70','Tumor maligno de las meninges'],
            ['C71','Tumor maligno del encéfalo',[['C71.0','Cerebro excepto lóbulos'],['C71.1','Lóbulo frontal'],['C71.2','Lóbulo temporal'],['C71.3','Lóbulo parietal'],['C71.4','Lóbulo occipital'],['C71.5','Ventrículo cerebral'],['C71.6','Cerebelo'],['C71.7','Tronco cerebral'],['C71.8','Lesión múltiple'],['C71.9','Encéfalo no especificado']]],
            ['C72','Tumor maligno de médula espinal nervios craneales y otras partes del SNC'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C73','Tumor maligno de la glándula tiroides'],['C74','Tumor maligno de la glándula suprarrenal'],
            ['C75','Tumor maligno de otras glándulas endocrinas'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C76','Tumor maligno de otros sitios y los mal definidos'],
            ['C77','Tumor maligno secundario de ganglios linfáticos'],
            ['C78','Tumor maligno secundario de órganos respiratorios y digestivos'],
            ['C79','Tumor maligno secundario de otros sitios'],
            ['C80','Tumor maligno sin especificación de sitio'],
        ]);


        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['C81','Enfermedad de Hodgkin',[['C81.0','Predominio linfocítico'],['C81.1','Esclerosis nodular'],['C81.2','Celularidad mixta'],['C81.3','Depleción linfocítica'],['C81.4','Hodgkin no especificado'],['C81.7','Otros Hodgkin'],['C81.9','Hodgkin no especificado']]],
            ['C82','Linfoma no Hodgkin folicular'],['C83','Linfoma no Hodgkin difuso'],
            ['C84','Linfomas de células T periféricas'],['C85','Linfoma no Hodgkin otro tipo y no especificado'],
            ['C86','Otras formas de linfoma de células T'],['C88','Trastornos inmunoproliferativos malignos'],
            ['C90','Mieloma múltiple y tumores de células plasmáticas'],
            ['C91','Leucemia linfoide',[['C91.0','Leucemia linfoblástica aguda'],['C91.1','Leucemia linfocítica crónica'],['C91.2','Leucemia linfocítica subaguda'],['C91.3','Leucemia prolinfocítica'],['C91.4','Leucemia de células pilosas'],['C91.5','Leucemia de células T adulta'],['C91.6','Leucemia linfomatosa'],['C91.7','Otras leucemias linfoides'],['C91.9','Leucemia linfoide no especificada']]],
            ['C92','Leucemia mieloide',[['C92.0','Leucemia mieloblástica aguda'],['C92.1','Leucemia mielocítica crónica'],['C92.2','Leucemia mielocítica subaguda'],['C92.3','Sarcoma mieloide'],['C92.4','Leucemia promielocítica aguda'],['C92.5','Leucemia mielomonocítica aguda'],['C92.6','Leucemia mielomonocítica crónica'],['C92.7','Otras leucemias mieloides'],['C92.9','Leucemia mieloide no especificada']]],
            ['C93','Leucemia monocítica'],['C94','Otras leucemias de tipo celular especificado'],
            ['C95','Leucemia de tipo celular no especificado'],
            ['C96','Otros tumores del tejido linfático hematopoyético y afines'],
            ['C97','Tumores malignos de sitios independientes múltiples'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['D00','Carcinoma in situ de cavidad oral esófago y estómago'],
            ['D01','Carcinoma in situ de otros órganos digestivos'],
            ['D02','Carcinoma in situ del sistema respiratorio y oído medio'],
            ['D03','Melanoma in situ'],['D04','Carcinoma in situ de la piel'],
            ['D05','Carcinoma in situ de la mama'],['D06','Carcinoma in situ del cuello del útero'],
            ['D07','Carcinoma in situ de otros órganos genitales'],['D09','Carcinoma in situ de otros sitios'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['D10','Tumor benigno de la boca y faringe'],['D11','Tumor benigno de las glándulas salivales mayores'],
            ['D12','Tumor benigno del colon recto conducto anal y ano'],
            ['D13','Tumor benigno de otras partes del sistema digestivo'],
            ['D14','Tumor benigno del sistema respiratorio y oído medio'],
            ['D15','Tumor benigno de otros órganos intratorácicos'],
            ['D16','Tumor benigno del hueso y cartílago articular'],
            ['D17','Tumor benigno lipomatoso',[['D17.0','Lipoma piel y tejido subcutáneo'],['D17.1','Lipoma tejidos intratorácicos'],['D17.2','Lipoma intraabdominal'],['D17.3','Lipoma nervios periféricos'],['D17.4','Lipoma otros órganos'],['D17.5','Lipoma no especificado']]],
            ['D18','Hemangioma y linfangioma'],['D19','Tumor benigno de tejido mesotelial'],
            ['D20','Tumor benigno del peritoneo y retroperitoneo'],['D21','Otros tumores benignos tejido conjuntivo'],
            ['D22','Nevo melanocítico'],['D23','Otros tumores benignos de la piel'],['D24','Tumor benigno de la mama'],
            ['D25','Leiomioma del útero',[['D25.0','Leiomioma submucoso'],['D25.1','Leiomioma intramural'],['D25.2','Leiomioma subseroso'],['D25.9','Leiomioma uterino no especificado']]],
            ['D26','Otros tumores benignos del útero'],['D27','Tumor benigno del ovario'],
            ['D28','Tumor benigno de otros órganos genitales femeninos'],['D29','Tumor benigno de órganos genitales masculinos'],
            ['D30','Tumor benigno de los órganos urinarios'],['D31','Tumor benigno del ojo y sus anexos'],
            ['D32','Tumor benigno de las meninges'],['D33','Tumor benigno del encéfalo y otras partes del SNC'],
            ['D34','Tumor benigno de la glándula tiroides'],['D35','Tumor benigno de otras glándulas endocrinas'],
            ['D36','Tumor benigno de otros sitios y los no especificados'],
        ]);

        $this->addCodes($codes, 'II', 'Neoplasias', [
            ['D37','Tumor comportamiento incierto de cavidad oral y órganos digestivos'],
            ['D38','Tumor comportamiento incierto de oído medio sistema respiratorio e intratorácicos'],
            ['D39','Tumor comportamiento incierto de órganos genitales femeninos'],
            ['D40','Tumor comportamiento incierto de órganos genitales masculinos'],
            ['D41','Tumor comportamiento incierto de los órganos urinarios'],
            ['D42','Tumor comportamiento incierto de las meninges'],
            ['D43','Tumor comportamiento incierto del encéfalo y SNC'],
            ['D44','Tumor comportamiento incierto de las glándulas endocrinas'],
            ['D45','Policitemia vera'],['D46','Síndromes mielodisplásicos'],
            ['D47','Otros tumores comportamiento incierto tejido linfático y hematopoyético'],
            ['D48','Tumor comportamiento incierto de otros sitios y los no especificados'],
        ]);


        // CAPÍTULO III: Sangre e inmunidad
        $this->addCodes($codes, 'III', 'Enfermedades de la sangre y órganos hematopoyéticos y ciertos trastornos que afectan el mecanismo de la inmunidad', [
            ['D50','Anemia ferropénica',[['D50.0','Anemia por pérdida crónica'],['D50.1','Disfagia ferropénica'],['D50.8','Otras anemias ferropénicas'],['D50.9','Anemia ferropénica no especificada']]],
            ['D51','Anemia por deficiencia de vitamina B12'],['D52','Anemia por deficiencia de folato'],
            ['D53','Otras anemias nutricionales'],['D55','Anemia debida a trastornos enzimáticos'],
            ['D56','Talasemia'],['D57','Trastornos falciformes'],['D58','Otras anemias hemolíticas hereditarias'],
            ['D59','Anemia hemolítica adquirida'],['D60','Eritroblastopenia adquirida'],
            ['D61','Otras anemias aplásticas'],['D62','Anemia aguda posthemorrágica'],
            ['D63','Anemia en enfermedades crónicas'],['D64','Otras anemias'],
        ]);

        $this->addCodes($codes, 'III', 'Enfermedades de la sangre y órganos hematopoyéticos y ciertos trastornos que afectan el mecanismo de la inmunidad', [
            ['D65','Coagulación intravascular diseminada'],['D66','Deficiencia hereditaria factor VIII'],
            ['D67','Deficiencia hereditaria factor IX'],['D68','Otros defectos de la coagulación'],
            ['D69','Púrpura y otras afecciones hemorrágicas'],
        ]);

        $this->addCodes($codes, 'III', 'Enfermedades de la sangre y órganos hematopoyéticos y ciertos trastornos que afectan el mecanismo de la inmunidad', [
            ['D70','Agranulocitosis'],['D71','Trastornos funcionales de neutrófilos'],
            ['D72','Otros trastornos de leucocitos'],['D73','Enfermedades del bazo'],
            ['D74','Metahemoglobinemia'],['D75','Otras enfermedades de la sangre y órganos hematopoyéticos'],
            ['D76','Ciertas enfermedades del tejido linforreticular'],
            ['D77','Otros trastornos de la sangre en enfermedades clasificadas en otra parte'],
        ]);

        $this->addCodes($codes, 'III', 'Enfermedades de la sangre y órganos hematopoyéticos y ciertos trastornos que afectan el mecanismo de la inmunidad', [
            ['D80','Inmunodeficiencia con predominio de defectos de anticuerpos'],
            ['D81','Inmunodeficiencias combinadas'],['D82','Inmunodeficiencia asociada con otros defectos mayores'],
            ['D83','Inmunodeficiencia variable común'],['D84','Otras inmunodeficiencias'],
            ['D86','Sarcoidosis'],['D89','Otros trastornos que afectan el mecanismo de la inmunidad'],
        ]);

        // CAPÍTULO IV: Endocrinas nutricionales y metabólicas
        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E00','Síndrome congénito por deficiencia de yodo'],['E01','Trastornos tiroideos por deficiencia de yodo'],
            ['E02','Hipotiroidismo subclínico por deficiencia de yodo'],['E03','Otros hipotiroidismos'],
            ['E04','Otros bocios no tóxicos'],['E05','Tirotoxicosis'],['E06','Tiroiditis'],['E07','Otros trastornos tiroideos'],
        ]);

        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E10','Diabetes mellitus tipo 1',[['E10.0','DM1 con coma'],['E10.1','DM1 con cetoacidosis'],['E10.2','DM1 con complicaciones renales'],['E10.3','DM1 con complicaciones oftálmicas'],['E10.4','DM1 con complicaciones neurológicas'],['E10.5','DM1 con complicaciones circulatorias'],['E10.6','DM1 con otras complicaciones'],['E10.7','DM1 con múltiples complicaciones'],['E10.8','DM1 con complicaciones no especificadas'],['E10.9','DM1 sin complicaciones']]],
            ['E11','Diabetes mellitus tipo 2',[['E11.0','DM2 con coma'],['E11.1','DM2 con cetoacidosis'],['E11.2','DM2 con complicaciones renales'],['E11.3','DM2 con complicaciones oftálmicas'],['E11.4','DM2 con complicaciones neurológicas'],['E11.5','DM2 con complicaciones circulatorias'],['E11.6','DM2 con otras complicaciones'],['E11.7','DM2 con múltiples complicaciones'],['E11.8','DM2 con complicaciones no especificadas'],['E11.9','DM2 sin complicaciones']]],
            ['E12','Diabetes mellitus asociada con desnutrición'],['E13','Otros tipos especificados de DM'],
            ['E14','Diabetes mellitus no especificada'],
        ]);

        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E15','Coma hipoglucémico no diabético'],['E16','Otros trastornos de la secreción interna del páncreas'],
        ]);

        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E20','Hipoparatiroidismo'],['E21','Hiperparatiroidismo'],['E22','Hiperfunción de la hipófisis'],
            ['E23','Hipofunción y otros trastornos de la hipófisis'],['E24','Síndrome de Cushing'],
            ['E25','Trastornos adrenogenitales'],['E26','Hiperaldosteronismo'],
            ['E27','Otros trastornos de la glándula suprarrenal'],['E28','Disfunción ovárica'],
            ['E29','Disfunción testicular'],['E30','Trastornos de la pubertad'],
            ['E31','Trastornos poliglandulares'],['E32','Enfermedades del timo'],
            ['E34','Otros trastornos endocrinos'],['E35','Trastornos endocrinos en otras enfermedades'],
        ]);

        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E40','Kwashiorkor'],['E41','Marasmo nutricional'],['E42','Kwashiorkor marasmático'],
            ['E43','Desnutrición severa no especificada'],['E44','Desnutrición moderada y leve'],
            ['E45','Retraso del desarrollo por desnutrición'],['E46','Desnutrición no especificada'],
        ]);

        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E50','Deficiencia de vitamina A'],['E51','Deficiencia de tiamina'],['E52','Deficiencia de niacina'],
            ['E53','Deficiencia de otras vitaminas del grupo B'],['E54','Deficiencia de vitamina C'],
            ['E55','Deficiencia de vitamina D'],['E56','Otras deficiencias de vitaminas'],
            ['E58','Deficiencia de calcio'],['E59','Deficiencia de selenio'],['E60','Deficiencia de zinc'],
            ['E61','Deficiencia de otros elementos'],['E63','Otras deficiencias nutricionales'],
            ['E64','Secuelas de desnutrición y otras deficiencias nutricionales'],
        ]);

        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E65','Adiposidad localizada'],['E66','Obesidad'],['E67','Otros tipos de hiperalimentación'],
            ['E68','Secuelas de hiperalimentación'],
        ]);

        $this->addCodes($codes, 'IV', 'Enfermedades endocrinas nutricionales y metabólicas', [
            ['E70','Trastornos metabolismo aminoácidos aromáticos'],['E71','Trastornos metabolismo aminoácidos cadena ramificada'],
            ['E72','Otros trastornos metabolismo aminoácidos'],['E73','Intolerancia a la lactosa'],
            ['E74','Otros trastornos metabolismo carbohidratos'],['E75','Trastornos metabolismo esfingolípidos'],
            ['E76','Trastornos metabolismo glucosaminoglicanos'],['E77','Trastornos metabolismo glucoproteínas'],
            ['E78','Trastornos metabolismo lipoproteínas'],['E79','Trastornos metabolismo purinas y pirimidinas'],
            ['E80','Trastornos metabolismo porfirinas y bilirrubina'],['E83','Trastornos metabolismo minerales'],
            ['E84','Fibrosis quística'],['E85','Amiloidosis'],['E86','Depleción del volumen de líquidos'],
            ['E87','Otros trastornos líquidos electrolitos y equilibrio ácido-básico'],['E88','Otros trastornos metabólicos'],
            ['E89','Trastornos endocrinos postprocedimiento'],['E90','Trastornos metabólicos en otras enfermedades'],
        ]);


        // CAPÍTULO V: Trastornos mentales
        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F00','Demencia en enfermedad de Alzheimer'],['F01','Demencia vascular'],
            ['F02','Demencia en otras enfermedades'],['F03','Demencia no especificada'],
            ['F04','Síndrome amnésico orgánico'],['F05','Delirio'],['F06','Orgánico por lesión cerebral'],
            ['F07','Personalidad orgánica'],['F09','Trastorno orgánico no especificado'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F10','Alcohol',[['F10.0','Intoxicación aguda'],['F10.1','Uso perjudicial'],['F10.2','Síndrome de dependencia'],['F10.3','Síndrome de abstinencia'],['F10.4','Abstinencia con delirium'],['F10.5','Trastorno psicótico'],['F10.6','Síndrome amnésico'],['F10.7','Trastorno residual'],['F10.8','Otros trastornos'],['F10.9','Trastorno no especificado']]],
            ['F11','Opiáceos'],['F12','Cannabinoides'],['F13','Sedantes e hipnóticos'],['F14','Cocaína'],
            ['F15','Estimulantes'],['F16','Alucinógenos'],
            ['F17','Tabaco',[['F17.0','Intoxicación aguda'],['F17.1','Uso perjudicial'],['F17.2','Síndrome de dependencia'],['F17.3','Síndrome de abstinencia'],['F17.4','Abstinencia con delirium'],['F17.5','Trastorno psicótico'],['F17.6','Síndrome amnésico'],['F17.7','Trastorno residual'],['F17.8','Otros trastornos'],['F17.9','Trastorno no especificado']]],
            ['F18','Solventes volátiles'],['F19','Múltiples drogas y otras sustancias'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F20','Esquizofrenia',[['F20.0','Paranoide'],['F20.1','Hebefrénica'],['F20.2','Catatónica'],['F20.3','Indiferenciada'],['F20.4','Depresión postesquizofrénica'],['F20.5','Residual'],['F20.6','Simple'],['F20.8','Otros tipos'],['F20.9','No especificada']]],
            ['F21','Trastorno esquizotípico'],['F22','Trastornos delirantes persistentes'],
            ['F23','Trastornos psicóticos agudos y transitorios'],['F24','Trastorno delirante inducido'],
            ['F25','Trastornos esquizoafectivos'],['F28','Otros psicóticos no orgánicos'],['F29','Psicosis no orgánica no especificada'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F30','Episodio maníaco',[['F30.0','Hipomanía'],['F30.1','Manía sin síntomas psicóticos'],['F30.2','Manía con síntomas psicóticos'],['F30.8','Otros episodios maníacos'],['F30.9','Episodio maníaco no especificado']]],
            ['F31','Trastorno bipolar',[['F31.0','Bipolar hipomaníaco'],['F31.1','Bipolar maníaco sin psicosis'],['F31.2','Bipolar maníaco con psicosis'],['F31.3','Bipolar depresivo moderado'],['F31.4','Bipolar depresivo severo sin psicosis'],['F31.5','Bipolar depresivo severo con psicosis'],['F31.6','Bipolar mixto'],['F31.7','Bipolar en remisión'],['F31.8','Otros bipolares'],['F31.9','Bipolar no especificado']]],
            ['F32','Episodio depresivo mayor',[['F32.0','Depresión leve'],['F32.1','Depresión moderada'],['F32.2','Depresión severa sin psicosis'],['F32.3','Depresión severa con psicosis'],['F32.8','Otros episodios depresivos'],['F32.9','Episodio depresivo no especificado']]],
            ['F33','Trastorno depresivo recurrente',[['F33.0','Recurrente leve'],['F33.1','Recurrente moderado'],['F33.2','Recurrente severo sin psicosis'],['F33.3','Recurrente severo con psicosis'],['F33.4','Recurrente en remisión'],['F33.8','Otros recurrentes'],['F33.9','Recurrente no especificado']]],
            ['F34','Trastornos del humor persistentes'],['F38','Otros trastornos del humor'],['F39','Trastorno del humor no especificado'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F40','Trastornos fóbico-ansiosos',[['F40.0','Agorafobia'],['F40.1','Fobias sociales'],['F40.2','Fobias específicas'],['F40.8','Otros fóbicos'],['F40.9','Fóbico no especificado']]],
            ['F41','Otros trastornos de ansiedad',[['F41.0','Trastorno de pánico'],['F41.1','Ansiedad generalizada'],['F41.2','Ansiedad mixta depresiva'],['F41.3','Otros mixtos'],['F41.8','Otros ansiedad'],['F41.9','Ansiedad no especificada']]],
            ['F42','Trastorno obsesivo-compulsivo',[['F42.0','Predominio pensamientos'],['F42.1','Predominio compulsiones'],['F42.2','Mixto'],['F42.8','Otros TOC'],['F42.9','TOC no especificado']]],
            ['F43','Reacción al estrés grave y trastornos de adaptación',[['F43.0','Estrés agudo'],['F43.1','TEPT'],['F43.2','Trastorno adaptación'],['F43.8','Otras reacciones'],['F43.9','Reacción no especificada']]],
            ['F44','Trastornos disociativos',[['F44.0','Amnesia disociativa'],['F44.1','Fuga disociativa'],['F44.2','Estupor disociativo'],['F44.3','Estados de trance'],['F44.4','Trastornos motores'],['F44.5','Convulsiones disociativas'],['F44.6','Anestesia disociativa'],['F44.7','Trastorno mixto'],['F44.8','Otros disociativos'],['F44.9','Disociativo no especificado']]],
            ['F45','Trastornos somatomorfos',[['F45.0','Somatización'],['F45.1','Somatomorfo indiferenciado'],['F45.2','Hipocondría'],['F45.3','Disfunción autónoma'],['F45.4','Dolor somatomorfo persistente'],['F45.8','Otros somatomorfos'],['F45.9','Somatomorfo no especificado']]],
            ['F48','Otros trastornos neuróticos',[['F48.0','Neurastenia'],['F48.1','Despersonalización'],['F48.8','Otros neuróticos'],['F48.9','Neurótico no especificado']]],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F50','Trastornos de la conducta alimentaria',[['F50.0','Anorexia nerviosa'],['F50.1','Anorexia atípica'],['F50.2','Bulimia nerviosa'],['F50.3','Bulimia atípica'],['F50.4','Hiperfagia'],['F50.5','Vómitos'],['F50.8','Otros alimentarios'],['F50.9','Alimentario no especificado']]],
            ['F51','Trastornos del sueño no orgánicos'],['F52','Disfunción sexual no orgánica'],
            ['F53','Trastornos mentales del puerperio'],['F54','Factores psicológicos en otras enfermedades'],
            ['F55','Abuso de sustancias que no producen dependencia'],['F59','Trastornos fisiológicos no especificados'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F60','Trastornos específicos de la personalidad',[['F60.0','Paranoide'],['F60.1','Esquizoide'],['F60.2','Disocial'],['F60.3','Impulsivo'],['F60.4','Límite'],['F60.5','Histriónico'],['F60.6','Anancástico'],['F60.7','Dependiente'],['F60.8','Otros'],['F60.9','No especificado']]],
            ['F61','Trastornos mixtos de la personalidad'],
            ['F62','Cambio persistente de la personalidad no debido a lesión cerebral'],
            ['F63','Trastornos de los hábitos y de los impulsos',[['F63.0','Juego patológico'],['F63.1','Piromanía'],['F63.2','Cleptomanía'],['F63.3','Tricotilomanía'],['F63.8','Otros'],['F63.9','No especificado']]],
            ['F64','Trastornos de la identidad de género'],['F65','Trastornos de la preferencia sexual'],
            ['F66','Trastornos psicológicos del desarrollo sexual'],['F68','Otros trastornos personalidad y comportamiento'],
            ['F69','Trastorno personalidad no especificado'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F70','Retraso mental leve'],['F71','Retraso mental moderado'],['F72','Retraso mental severo'],
            ['F73','Retraso mental profundo'],['F78','Otros retrasos mentales'],['F79','Retraso mental no especificado'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F80','Trastornos del habla y lenguaje'],['F81','Trastornos habilidades escolares'],
            ['F82','Trastorno función motora'],['F83','Trastornos mixtos del desarrollo'],
            ['F84','Trastornos generalizados del desarrollo',[['F84.0','Autismo infantil'],['F84.1','Autismo atípico'],['F84.2','Síndrome de Rett'],['F84.3','Trastorno desintegrativo infantil'],['F84.4','Trastorno hiperactivo asociado'],['F84.5','Síndrome de Asperger'],['F84.8','Otros generalizados'],['F84.9','Generalizado no especificado']]],
            ['F88','Otros trastornos del desarrollo psicológico'],['F89','Trastorno desarrollo psicológico no especificado'],
        ]);

        $this->addCodes($codes, 'V', 'Trastornos mentales y del comportamiento', [
            ['F90','Trastornos hipercinéticos',[['F90.0','TDAH combinado'],['F90.1','TDAH inatento'],['F90.8','Otros hipercinéticos'],['F90.9','Hipercinético no especificado']]],
            ['F91','Trastornos de la conducta',[['F91.0','Limitado al hogar'],['F91.1','No socializado'],['F91.2','Socializado'],['F91.3','Desafiante oposicionista'],['F91.8','Otros'],['F91.9','No especificado']]],
            ['F92','Trastornos mixtos conducta y emociones'],['F93','Trastornos emocionales inicio infancia'],
            ['F94','Trastornos funcionamiento social inicio infancia'],
            ['F95','Trastornos por tics',[['F95.0','Tic transitorio'],['F95.1','Tic motor crónico'],['F95.2','Tic vocal crónico'],['F95.8','Otros tics'],['F95.9','Tic no especificado']]],
            ['F98','Otros trastornos comportamiento y emocionales infancia'],['F99','Trastorno mental no especificado'],
        ]);


        // CAPÍTULO VI: Sistema nervioso
        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G00','Meningitis bacteriana no clasificada'],['G01','Meningitis en enfermedades bacterianas'],
            ['G02','Meningitis en otras enfermedades infecciosas'],['G03','Meningitis debida a otras causas'],
            ['G04','Encefalitis mielitis y encefalomielitis'],['G05','Encefalitis mielitis en enfermedades clasificadas'],
            ['G06','Absceso intracraneal e intrarraquídeo'],['G07','Absceso en enfermedades clasificadas en otra parte'],
            ['G08','Flebitis y tromboflebitis intracraneal'],['G09','Secuelas enfermedades inflamatorias SNC'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G10','Enfermedad de Huntington'],['G11','Ataxia hereditaria'],
            ['G12','Atrofia muscular espinal y síndromes afines'],
            ['G13','Atrofias sistémicas que afectan SNC en enfermedades clasificadas'],
            ['G14','Síndrome postpoliomielítico'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G20','Enfermedad de Parkinson',[['G20.0','Parkinson primario'],['G20.9','Parkinson no especificado']]],
            ['G21','Parkinsonismo secundario'],['G22','Parkinsonismo en enfermedades clasificadas'],
            ['G23','Otras enfermedades degenerativas ganglios basales'],['G24','Distonía'],
            ['G25','Otros trastornos extrapiramidales y del movimiento'],
            ['G26','Trastornos extrapiramidales en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G30','Enfermedad de Alzheimer',[['G30.0','Alzheimer inicio temprano'],['G30.1','Alzheimer inicio tardío'],['G30.8','Otros Alzheimer'],['G30.9','Alzheimer no especificado']]],
            ['G31','Otras enfermedades degenerativas SNC'],['G32','Otros trastornos degenerativos SNC en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G35','Esclerosis múltiple',[['G35.0','EM con brotes'],['G35.1','EM primaria progresiva'],['G35.2','EM secundaria progresiva'],['G35.3','EM progresiva recurrente'],['G35.9','EM no especificada']]],
            ['G36','Otras desmielinizantes agudas'],['G37','Otras enfermedades desmielinizantes SNC'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G40','Epilepsia',[['G40.0','Crisis focales sin alteración conciencia'],['G40.1','Crisis focales con alteración conciencia'],['G40.2','Crisis focales generalizadas secundariamente'],['G40.3','Crisis generalizadas idiopáticas'],['G40.4','Otras crisis generalizadas'],['G40.5','Crisis mioclónicas'],['G40.6','Crisis de ausencia'],['G40.7','Crisis parciales complejas'],['G40.8','Otras epilepsias'],['G40.9','Epilepsia no especificada']]],
            ['G41','Estado epiléptico',[['G41.0','Estado gran mal'],['G41.1','Estado ausencia'],['G41.2','Estado parcial complejo'],['G41.8','Otros estados'],['G41.9','Estado no especificado']]],
            ['G43','Migraña',[['G43.0','Migraña sin aura'],['G43.1','Migraña con aura'],['G43.2','Estado migrañoso'],['G43.3','Migraña complicada'],['G43.8','Otras migrañas'],['G43.9','Migraña no especificada']]],
            ['G44','Otras cefaleas',[['G44.0','Cefalea en racimos'],['G44.1','Cefalea tensional'],['G44.2','Cefalea crónica diaria'],['G44.3','Cefalea postraumática'],['G44.4','Cefalea por medicación'],['G44.8','Otras cefaleas']]],
            ['G45','Ataques isquémicos transitorios'],['G46','Síndromes vasculares cerebrales'],
            ['G47','Trastornos del sueño',[['G47.0','Insomnio'],['G47.1','Hipersomnia'],['G47.2','Trastorno ciclo sueño'],['G47.3','Apnea del sueño'],['G47.4','Narcolepsia'],['G47.8','Otros sueño'],['G47.9','Trastorno sueño no especificado']]],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G50','Trastornos del trigémino',[['G50.0','Neuralgia trigémino'],['G50.1','Dolor atípico facial'],['G50.8','Otros trigémino'],['G50.9','Trigémino no especificado']]],
            ['G51','Trastornos del nervio facial',[['G51.0','Parálisis de Bell'],['G51.1','Geniculado'],['G51.2','Síndrome de Melkersson'],['G51.3','Espasmo hemifacial'],['G51.4','Mioquimia facial'],['G51.8','Otros facial'],['G51.9','Facial no especificado']]],
            ['G52','Trastornos de otros nervios craneales'],['G53','Trastornos nervios craneales en enfermedades clasificadas'],
            ['G54','Trastornos de raíces y plexos nerviosos'],['G55','Compresión de raíces en enfermedades clasificadas'],
            ['G56','Mononeuropatías miembro superior',[['G56.0','Síndrome túnel carpiano'],['G56.1','Neuralgia mediano'],['G56.2','Lesión cubital'],['G56.3','Lesión radial'],['G56.4','Causalgia miembro superior'],['G56.8','Otras mononeuropatías MS'],['G56.9','Mononeuropatía MS no especificada']]],
            ['G57','Mononeuropatías miembro inferior'],['G58','Otras mononeuropatías'],
            ['G59','Mononeuropatías en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G60','Neuropatía hereditaria e idiopática'],
            ['G61','Polineuropatía inflamatoria',[['G61.0','Síndrome Guillain-Barré'],['G61.1','Neuropatía sérica'],['G61.8','Otras inflamatorias'],['G61.9','Inflamatoria no especificada']]],
            ['G62','Otras polineuropatías'],['G63','Polineuropatía en enfermedades clasificadas'],
            ['G64','Otros trastornos del sistema nervioso periférico'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G70','Miastenia gravis y otros trastornos neuromusculares'],['G71','Trastornos musculares primarios'],
            ['G72','Otras miopatías'],['G73','Trastornos unión neuromuscular en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G80','Parálisis cerebral infantil'],['G81','Hemiplejía'],['G82','Paraplejía y tetraplejía'],
            ['G83','Otros síndromes paralíticos'],
        ]);

        $this->addCodes($codes, 'VI', 'Enfermedades del sistema nervioso', [
            ['G90','Trastornos del sistema nervioso autónomo'],['G91','Hidrocefalia'],['G92','Encefalopatía tóxica'],
            ['G93','Otros trastornos del encéfalo'],['G94','Trastornos del encéfalo en enfermedades clasificadas'],
            ['G95','Otras enfermedades de la médula espinal'],['G96','Otros trastornos del SNC'],
            ['G97','Trastornos postprocedimiento del sistema nervioso'],['G98','Otros trastornos del sistema nervioso'],
            ['G99','Trastornos del sistema nervioso en enfermedades clasificadas'],
        ]);

        // CAPÍTULO VII: Ojo y anexos
        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H00','Orzuelo y chalación'],['H01','Otras inflamaciones del párpado'],['H02','Otros trastornos del párpado'],
            ['H03','Trastornos del párpado en enfermedades clasificadas'],['H04','Trastornos del aparato lagrimal'],
            ['H05','Trastornos de la órbita'],['H06','Trastornos lagrimal y órbita en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H10','Conjuntivitis',[['H10.0','Conjuntivitis mucopurulenta'],['H10.1','Conjuntivitis atópica aguda'],['H10.2','Otras conjuntivitis agudas'],['H10.3','Conjuntivitis crónica'],['H10.4','Conjuntivitis folicular crónica'],['H10.5','Blefaroconjuntivitis'],['H10.8','Otras conjuntivitis'],['H10.9','Conjuntivitis no especificada']]],
            ['H11','Otros trastornos de la conjuntiva'],['H13','Trastornos conjuntiva en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H15','Trastornos de la esclerótica'],['H16','Queratitis'],['H17','Cicatrices y opacidades corneales'],
            ['H18','Otros trastornos de la córnea'],['H19','Trastornos esclera y córnea en enfermedades clasificadas'],
            ['H20','Iridociclitis',[['H20.0','Iridociclitis aguda'],['H20.1','Iridociclitis crónica'],['H20.2','Iridociclitis lens'],['H20.8','Otras iridociclitis'],['H20.9','Iridociclitis no especificada']]],
            ['H21','Otros trastornos del iris y cuerpo ciliar'],['H22','Trastornos iris en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H25','Catarata senil',[['H25.0','Catarata senil incipiente'],['H25.1','Catarata senil nuclear'],['H25.2','Catarata senil tipo morgagniana'],['H25.8','Otras cataratas seniles'],['H25.9','Catarata senil no especificada']]],
            ['H26','Otras cataratas',[['H26.0','Catarata infantil y juvenil'],['H26.1','Catarata traumática'],['H26.2','Catarata complicada'],['H26.3','Catarata medicamentosa'],['H26.4','Catarata secundaria'],['H26.8','Otras cataratas'],['H26.9','Catarata no especificada']]],
            ['H27','Otros trastornos del cristalino'],['H28','Catarata en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H30','Inflamación coriorretiniana'],['H31','Otros trastornos de la coroides'],
            ['H32','Trastornos coriorretinianos en enfermedades clasificadas'],
            ['H33','Desprendimiento y desgarro retina',[['H33.0','Desgarro retina'],['H33.1','Retinosquisis'],['H33.2','Desprendimiento seroso'],['H33.3','Desgarro sin desprendimiento'],['H33.4','Desprendimiento traccional'],['H33.5','Otros desprendimientos']]],
            ['H34','Oclusión vascular de la retina'],
            ['H35','Otros trastornos de la retina',[['H35.0','Retinopatía fondo'],['H35.1','Retinopatía prematuridad'],['H35.2','Otras retinopatías'],['H35.3','Degeneración macular'],['H35.4','Edema macular'],['H35.5','Distrofia hereditaria'],['H35.6','Hemorragia retiniana'],['H35.7','Desprendimiento epitelio'],['H35.8','Otros retina'],['H35.9','Retina no especificado']]],
            ['H36','Trastornos retina en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H40','Glaucoma',[['H40.0','Glaucoma sospechoso'],['H40.1','Glaucoma primario ángulo abierto'],['H40.2','Glaucoma primario ángulo cerrado'],['H40.3','Glaucoma secundario'],['H40.4','Glaucoma congénito'],['H40.5','Glaucoma desarrollo'],['H40.6','Glaucoma medicamentoso'],['H40.8','Otros glaucoma'],['H40.9','Glaucoma no especificado']]],
            ['H42','Glaucoma en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H43','Trastornos del cuerpo vítreo'],['H44','Trastornos del globo ocular'],
            ['H45','Trastornos vítreo y globo ocular en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H46','Neuritis óptica'],['H47','Otros trastornos del nervio óptico y vías ópticas'],
            ['H48','Trastornos nervio óptico en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H49','Estrabismo paralítico'],['H50','Otros estrabismos'],
            ['H51','Otros trastornos movimientos binoculares'],['H52','Trastornos de la refracción'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H53','Alteraciones de la visión'],['H54','Ceguera y visión subnormal'],
        ]);

        $this->addCodes($codes, 'VII', 'Enfermedades del ojo y sus anexos', [
            ['H55','Nistagmo'],['H57','Otros trastornos del ojo'],['H58','Trastornos del ojo en enfermedades clasificadas'],
            ['H59','Trastornos postprocedimiento del ojo'],
        ]);

        // CAPÍTULO VIII: Oído y mastoides
        $this->addCodes($codes, 'VIII', 'Enfermedades del oído y de la apófisis mastoides', [
            ['H60','Otitis externa'],['H61','Otros trastornos del oído externo'],
            ['H62','Trastornos oído externo en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VIII', 'Enfermedades del oído y de la apófisis mastoides', [
            ['H65','Otitis media no supurativa'],['H66','Otitis media supurativa y no especificada'],
            ['H67','Otitis media en enfermedades clasificadas'],['H68','Inflamación y obstrucción trompa Eustaquio'],
            ['H69','Otros trastornos trompa Eustaquio'],['H70','Mastoiditis y afecciones relacionadas'],
            ['H71','Colesteatoma del oído medio'],['H72','Perforación membrana timpánica'],
            ['H73','Otros trastornos del oído medio'],['H74','Otros trastornos apófisis mastoides'],
            ['H75','Otros trastornos oído medio y mastoides en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'VIII', 'Enfermedades del oído y de la apófisis mastoides', [
            ['H80','Otosclerosis'],['H81','Trastornos de la función vestibular'],
            ['H82','Síndromes vertiginosos en enfermedades clasificadas'],['H83','Otros trastornos del oído interno'],
        ]);

        $this->addCodes($codes, 'VIII', 'Enfermedades del oído y de la apófisis mastoides', [
            ['H90','Hipoacusia conductiva y neurosensorial'],['H91','Otra hipoacusia'],
            ['H92','Otalgia y otorrea'],['H93','Otros trastornos del oído'],
            ['H94','Trastornos del oído en enfermedades clasificadas'],
            ['H95','Trastornos postprocedimiento del oído y mastoides'],
        ]);


        // CAPÍTULO IX: Sistema circulatorio
        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I00','Fiebre reumática sin complicación cardíaca'],['I01','Carditis reumática aguda'],['I02','Corea reumática'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I05','Enfermedades reumáticas válvula mitral'],['I06','Enfermedades reumáticas válvula aórtica'],
            ['I07','Enfermedades reumáticas válvula tricúspide'],['I08','Enfermedades de múltiples válvulas'],
            ['I09','Otras enfermedades reumáticas del corazón'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I10','Hipertensión esencial (primaria)'],['I11','Enfermedad cardíaca hipertensiva'],
            ['I12','Enfermedad renal hipertensiva'],['I13','Enfermedad cardiorrenal hipertensiva'],
            ['I15','Hipertensión secundaria'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I20','Angina de pecho'],['I21','Infarto agudo del miocardio'],['I22','Infarto recurrente'],
            ['I23','Complicaciones del IAM'],['I24','Otras isquémicas agudas'],['I25','Enfermedad isquémica crónica'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I26','Embolia pulmonar'],['I27','Otras enfermedades cardiopulmonares'],['I28','Otras enfermedades vasos pulmonares'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I30','Pericarditis aguda'],['I31','Otras enfermedades del pericardio'],
            ['I32','Pericarditis en enfermedades clasificadas'],['I33','Endocarditis aguda y subaguda'],
            ['I34','Trastornos no reumáticos válvula mitral'],['I35','Trastornos no reumáticos válvula aórtica'],
            ['I36','Trastornos no reumáticos válvula tricúspide'],['I37','Trastornos válvula pulmonar'],
            ['I38','Endocarditis válvula no especificada'],['I39','Endocarditis en enfermedades clasificadas'],
            ['I40','Miocarditis aguda'],['I41','Miocarditis en enfermedades clasificadas'],
            ['I42','Miocardiopatía'],['I43','Miocardiopatía en enfermedades clasificadas'],
            ['I44','Bloqueo auriculoventricular'],['I45','Otros trastornos de la conducción'],
            ['I46','Paro cardíaco'],['I47','Taquicardia paroxística'],['I48','FA y aleteo auricular'],
            ['I49','Otras arritmias cardíacas'],['I50','Insuficiencia cardíaca'],
            ['I51','Complicaciones de enfermedades cardíacas'],['I52','Otras cardíacas en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I60','Hemorragia subaracnoidea'],['I61','Hemorragia intracerebral'],
            ['I62','Otras hemorragias intracraneales no traumáticas'],['I63','Infarto cerebral'],
            ['I64','ACV agudo no especificado'],['I65','Oclusión arterias precerebrales'],
            ['I66','Oclusión arterias cerebrales'],['I67','Otras enfermedades cerebrovasculares'],
            ['I68','Trastornos cerebrovasculares en enfermedades clasificadas'],['I69','Secuelas de enfermedad cerebrovascular'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I70','Aterosclerosis'],['I71','Aneurisma y disección aórtica'],['I72','Otros aneurismas'],
            ['I73','Otras enfermedades vasculares periféricas'],['I74','Embolia y trombosis arteriales'],
            ['I77','Otros trastornos arteriales'],['I78','Enfermedades de los capilares'],
            ['I79','Trastornos arteriales en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I80','Flebitis y tromboflebitis'],['I81','Trombosis de la vena porta'],
            ['I82','Otras embolias y trombosis venosas'],['I83','Várices extremidades inferiores'],
            ['I84','Hemorroides'],['I85','Várices esofágicas'],['I86','Várices de otros sitios'],
            ['I87','Trastornos venosos'],['I88','Linfadenitis inespecífica'],
            ['I89','Otros trastornos no infecciosos vasos linfáticos'],
        ]);

        $this->addCodes($codes, 'IX', 'Enfermedades del sistema circulatorio', [
            ['I95','Hipotensión'],['I97','Trastornos postprocedimiento sistema circulatorio'],
            ['I98','Otros trastornos sistema circulatorio en enfermedades clasificadas'],
            ['I99','Otros trastornos del sistema circulatorio no especificados'],
        ]);

        // CAPÍTULO X: Sistema respiratorio
        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J00','Rinofaringitis aguda (resfriado común)'],
            ['J01','Sinusitis aguda',[['J01.0','Sinusitis maxilar aguda'],['J01.1','Sinusitis frontal aguda'],['J01.2','Sinusitis etmoidal aguda'],['J01.3','Sinusitis esfenoidal aguda'],['J01.4','Pansinusitis aguda'],['J01.8','Otras sinusitis agudas'],['J01.9','Sinusitis aguda no especificada']]],
            ['J02','Faringitis aguda',[['J02.0','Faringitis estreptocócica'],['J02.8','Faringitis por otros microorganismos'],['J02.9','Faringitis aguda no especificada']]],
            ['J03','Amigdalitis aguda',[['J03.0','Amigdalitis estreptocócica'],['J03.8','Amigdalitis por otros microorganismos'],['J03.9','Amigdalitis aguda no especificada']]],
            ['J04','Laringitis y traqueítis aguda',[['J04.0','Laringitis aguda'],['J04.1','Traqueítis aguda'],['J04.2','Laringotraqueítis aguda']]],
            ['J05','Epiglotitis aguda'],
            ['J06','Infecciones agudas vías respiratorias superiores',[['J06.0','Laringofaringitis aguda'],['J06.8','Otras infecciones agudas vías superiores'],['J06.9','Infección aguda vías superiores no especificada']]],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J09','Influenza aviar'],
            ['J10','Influenza virus identificado',[['J10.0','Influenza con neumonía virus identificado'],['J10.1','Influenza con manifestaciones respiratorias virus identificado'],['J10.8','Influenza con otras manifestaciones virus identificado']]],
            ['J11','Influenza virus no identificado',[['J11.0','Influenza con neumonía virus no identificado'],['J11.1','Influenza con manifestaciones respiratorias virus no identificado'],['J11.8','Influenza con otras manifestaciones virus no identificado']]],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J12','Neumonía viral',[['J12.0','Neumonía por adenovirus'],['J12.1','Neumonía por VSR'],['J12.2','Neumonía por parainfluenza'],['J12.3','Neumonía por hantavirus'],['J12.8','Otras neumonías virales'],['J12.9','Neumonía viral no especificada']]],
            ['J13','Neumonía neumocócica'],['J14','Neumonía por Hemophilus influenzae'],
            ['J15','Neumonía bacteriana',[['J15.0','Neumonía por Klebsiella'],['J15.1','Neumonía por Pseudomonas'],['J15.2','Neumonía por estafilococo'],['J15.3','Neumonía por estreptococo B'],['J15.4','Neumonía por otros estreptococos'],['J15.5','Neumonía por E. coli'],['J15.6','Neumonía por otras bacterias aerobias'],['J15.7','Neumonía por Mycoplasma'],['J15.8','Otras neumonías bacterianas'],['J15.9','Neumonía bacteriana no especificada']]],
            ['J16','Neumonía por otros microorganismos infecciosos'],['J17','Neumonía en enfermedades clasificadas'],
            ['J18','Neumonía no especificada',[['J18.0','Bronconeumonía no especificada'],['J18.1','Neumonía lobar no especificada'],['J18.2','Neumonía hipostática'],['J18.8','Otras neumonías'],['J18.9','Neumonía no especificada']]],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J20','Bronquitis aguda',[['J20.0','Bronquitis por Mycoplasma'],['J20.1','Bronquitis por Haemophilus'],['J20.2','Bronquitis por estreptococo'],['J20.3','Bronquitis por Coxsackie'],['J20.4','Bronquitis por parainfluenza'],['J20.5','Bronquitis por VSR'],['J20.6','Bronquitis por rinovirus'],['J20.7','Bronquitis por echovirus'],['J20.8','Bronquitis por otros microorganismos'],['J20.9','Bronquitis aguda no especificada']]],
            ['J21','Bronquiolitis aguda'],['J22','IRA inferior no especificada'],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J30','Rinitis alérgica y vasomotora'],['J31','Rinitis rinofaringitis y faringitis crónicas'],
            ['J32','Sinusitis crónica',[['J32.0','Sinusitis maxilar crónica'],['J32.1','Sinusitis frontal crónica'],['J32.2','Sinusitis etmoidal crónica'],['J32.3','Sinusitis esfenoidal crónica'],['J32.4','Pansinusitis crónica'],['J32.8','Otras sinusitis crónicas'],['J32.9','Sinusitis crónica no especificada']]],
            ['J33','Pólipo nasal'],['J34','Otros trastornos nariz y senos paranasales'],
            ['J35','Trastornos crónicos amígdalas y adenoides'],['J36','Absceso periamigdalino'],
            ['J37','Laringitis crónica'],['J38','Enfermedades cuerdas vocales y laringe'],['J39','Otras enfermedades vías respiratorias superiores'],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J40','Bronquitis no especificada'],['J41','Bronquitis crónica simple y mucopurulenta'],
            ['J42','Bronquitis crónica no especificada'],['J43','Enfisema'],
            ['J44','EPOC',[['J44.0','EPOC con infección aguda'],['J44.1','EPOC con exacerbación aguda'],['J44.8','Otras EPOC'],['J44.9','EPOC no especificada']]],
            ['J45','Asma',[['J45.0','Asma alérgico extrínseco'],['J45.1','Asma no alérgico intrínseco'],['J45.8','Asma mixto'],['J45.9','Asma no especificado']]],
            ['J46','Estado asmático'],['J47','Bronquiectasias'],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J60','Neumoconiosis del minero de carbón'],['J61','Asbestosis'],['J62','Silicosis'],
            ['J63','Neumoconiosis por otros polvos inorgánicos'],['J64','Neumoconiosis no especificada'],
            ['J65','Neumoconiosis con tuberculosis'],['J66','Enf vías aéreas por polvos orgánicos'],
            ['J67','Neumonitis por hipersensibilidad'],['J68','Afecciones respiratorias por químicos'],
            ['J69','Neumonitis por sólidos y líquidos'],['J70','Afecciones respiratorias por otros agentes externos'],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J80','SDRA'],['J81','Edema pulmonar'],['J82','Eosinofilia pulmonar'],
            ['J84','Otras enfermedades pulmonares intersticiales'],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J85','Absceso del pulmón y mediastino'],['J86','Piotórax'],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J90','Derrame pleural no clasificado'],['J91','Derrame pleural en enfermedades clasificadas'],
            ['J92','Placa pleural'],['J93','Neumotórax'],['J94','Otras afecciones pleurales'],
        ]);

        $this->addCodes($codes, 'X', 'Enfermedades del sistema respiratorio', [
            ['J95','Trastornos postprocedimiento respiratorio'],
            ['J96','Insuficiencia respiratoria',[['J96.0','Insuficiencia respiratoria aguda'],['J96.1','Insuficiencia respiratoria crónica'],['J96.9','Insuficiencia respiratoria no especificada']]],
            ['J98','Otros trastornos respiratorios'],['J99','Trastornos respiratorios en enfermedades clasificadas'],
        ]);


        // CAPÍTULO XI: Sistema digestivo
        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K00','Trastornos desarrollo y erupción dientes'],['K01','Dientes incluidos e impactados'],
            ['K02','Caries dental'],['K03','Otras enfermedades tejidos duros dientes'],
            ['K04','Enfermedades pulpa y tejidos periapicales'],['K05','Gingivitis y enfermedades periodontales'],
            ['K06','Otros trastornos de la encía'],['K07','Anomalías dentofaciales'],
            ['K08','Otros trastornos de los dientes'],['K09','Quistes región oral'],
            ['K10','Otras enfermedades de los maxilares'],['K11','Enfermedades glándulas salivales'],
            ['K12','Estomatitis y lesiones afines'],['K13','Otras enfermedades mucosa oral'],['K14','Enfermedades de la lengua'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K20','Esofagitis'],['K21','ERGE'],['K22','Otros trastornos del esófago'],
            ['K23','Trastornos esófago en enfermedades clasificadas'],['K25','Úlcera gástrica'],
            ['K26','Úlcera duodenal'],['K27','Úlcera péptica sitio no especificado'],
            ['K28','Úlcera gastro-yeyunal'],['K29','Gastritis y duodenitis'],['K30','Dispepsia'],
            ['K31','Otros trastornos estómago y duodeno'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K35','Apendicitis aguda'],['K36','Otros tipos de apendicitis'],['K37','Apendicitis no especificada'],
            ['K38','Otros trastornos del apéndice'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K40','Hernia inguinal'],['K41','Hernia femoral'],['K42','Hernia umbilical'],
            ['K43','Hernia ventral'],['K44','Hernia diafragmática'],['K45','Otras hernias abdominales'],
            ['K46','Hernia abdominal no especificada'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K50','Enfermedad de Crohn'],['K51','Colitis ulcerosa'],['K52','Otras gastroenteritis no infecciosas'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K55','Trastornos vasculares del intestino'],['K56','Íleo y obstrucción intestinal'],
            ['K57','Enfermedad diverticular del intestino'],['K58','SII'],['K59','Otros funcionales del intestino'],
            ['K60','Fisura y fístula anal'],['K61','Absceso anal y rectal'],
            ['K62','Otras enfermedades del recto y ano'],['K63','Otras enfermedades del intestino'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K65','Peritonitis'],['K66','Otros trastornos del peritoneo'],
            ['K67','Trastornos peritoneales en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K70','Enfermedad alcohólica del hígado'],['K71','Enfermedad hepática tóxica'],
            ['K72','Insuficiencia hepática'],['K73','Hepatitis crónica no clasificada'],
            ['K74','Fibrosis y cirrosis del hígado'],['K75','Otras enfermedades inflamatorias del hígado'],
            ['K76','Otras enfermedades del hígado'],['K77','Trastornos hepáticos en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K80','Colelitiasis'],['K81','Colecistitis'],['K82','Otras enfermedades vesícula biliar'],
            ['K83','Otras enfermedades vías biliares'],['K85','Pancreatitis aguda'],
            ['K86','Otras enfermedades del páncreas'],
            ['K87','Trastornos vesícula biliar y páncreas en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XI', 'Enfermedades del sistema digestivo', [
            ['K90','Malabsorción intestinal'],['K91','Trastornos postprocedimiento digestivo'],
            ['K92','Otras enfermedades del sistema digestivo'],['K93','Trastornos digestivos en enfermedades clasificadas'],
        ]);

        // CAPÍTULO XII: Piel y tejido subcutáneo
        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L00','Síndrome estafilocócico de la piel'],['L01','Impétigo'],['L02','Absceso forúnculo y ántrax'],
            ['L03','Celulitis'],['L04','Linfadenitis aguda'],['L05','Quiste pilonidal'],['L08','Otras infecciones locales piel'],
        ]);

        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L10','Pénfigo'],['L11','Otros trastornos acantolíticos'],['L12','Penfigoide'],
            ['L13','Otros trastornos ampollares'],['L14','Trastornos ampollares en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L20','Dermatitis atópica'],['L21','Dermatitis seborreica'],['L22','Dermatitis del pañal'],
            ['L23','Dermatitis alérgica de contacto'],['L24','Dermatitis irritativa de contacto'],
            ['L25','Dermatitis de contacto no especificada'],['L26','Dermatitis exfoliativa'],
            ['L27','Dermatitis por sustancias ingeridas'],['L28','Liquen simple y prurigo'],['L29','Prurito'],
            ['L30','Otras dermatitis'],
        ]);

        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L40','Psoriasis'],['L41','Parapsoriasis'],['L42','Pitiriasis rosada'],['L43','Liquen plano'],
            ['L44','Otros papuloescamosos'],['L45','Papuloescamosos en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L50','Urticaria'],['L51','Eritema multiforme'],['L52','Eritema nudoso'],
            ['L53','Otras eritematosas'],['L54','Eritema en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L55','Quemadura solar'],['L56','Otros cambios por radiación UV'],['L57','Cambios por radiación crónica'],
            ['L58','Radiodermatitis'],['L59','Otros trastornos por radiación'],
        ]);

        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L60','Trastornos de las uñas'],['L62','Trastornos uñas en enfermedades clasificadas'],
            ['L63','Alopecia areata'],['L64','Alopecia androgénica'],['L65','Otra pérdida cabello no cicatricial'],
            ['L66','Alopecia cicatricial'],['L67','Anomalías color cabello'],['L68','Hipertricosis'],
            ['L70','Acné'],['L71','Rosácea'],['L72','Quistes foliculares'],['L73','Otros trastornos foliculares'],
            ['L74','Trastornos sudoríparas ecrinos'],['L75','Trastornos sudoríparas apocrinos'],
        ]);

        $this->addCodes($codes, 'XII', 'Enfermedades de la piel y el tejido subcutáneo', [
            ['L80','Vitíligo'],['L81','Otros trastornos pigmentación'],['L82','Queratosis seborreica'],
            ['L83','Acantosis nigricans'],['L84','Callos y callosidades'],['L85','Otros engrosamiento epidérmico'],
            ['L86','Queratodermia en enfermedades clasificadas'],['L87','Alteraciones eliminación transepidérmica'],
            ['L88','Pioderma gangrenoso'],['L89','Úlcera por presión'],['L90','Trastornos atróficos de la piel'],
            ['L91','Trastornos hipertróficos de la piel'],['L92','Trastornos granulomatosos de la piel'],
            ['L93','Lupus eritematoso'],['L94','Otros trastornos localizados tejido conjuntivo'],
            ['L95','Vasculitis limitada a la piel'],['L97','Úlcera miembro inferior'],
            ['L98','Otras enfermedades piel no clasificadas'],['L99','Trastornos piel en enfermedades clasificadas'],
        ]);

        // CAPÍTULO XIII: Sistema musculoesquelético
        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M00','Artritis piógena'],['M01','Artritis en enfermedades infecciosas'],['M02','Artropatías reactivas'],
            ['M03','Artritis postinfecciosas'],['M05','Artritis reumatoide seropositiva'],
            ['M06','Artritis reumatoide seronegativa'],['M07','Artropatías psoriásicas y enteropáticas'],
            ['M08','Artritis juvenil'],['M09','Artritis juvenil en enfermedades clasificadas'],
            ['M10','Gota'],['M11','Otras artropatías por cristales'],['M12','Otras artropatías especificadas'],
            ['M13','Otras artritis'],['M14','Artropatías en otras enfermedades'],
            ['M15','Poliartrosis'],['M16','Coxartrosis'],['M17','Gonartrosis'],
            ['M18','Artrosis primera carpometacarpiana'],['M19','Otras artrosis'],
            ['M20','Deformidades adquiridas de los dedos'],['M21','Otras deformidades adquiridas miembros'],
            ['M22','Trastornos de la rótula'],['M23','Trastornos internos de la rodilla'],
            ['M24','Otros trastornos articulares'],['M25','Otros trastornos articulares no clasificados'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M30','Poliarteritis nudosa'],['M31','Otras vasculopatías necrotizantes'],
            ['M32','Lupus eritematoso sistémico'],['M33','Dermatomiositis y polimiositis'],
            ['M34','Esclerosis sistémica'],['M35','Otras afecciones sistémicas tejido conjuntivo'],
            ['M36','Trastornos sistémicos tejido conjuntivo en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M40','Cifosis y lordosis'],['M41','Escoliosis'],['M42','Osteocondrosis de la columna'],
            ['M43','Otras deformidades de la columna'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M45','Espondilitis anquilosante'],['M46','Otras espondilopatías inflamatorias'],
            ['M47','Espondilosis'],['M48','Otras espondilopatías'],['M49','Espondilopatías en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M50','Trastornos disco cervical'],['M51','Trastornos disco lumbar y otros'],
            ['M53','Otras dorsopatías no clasificadas'],
            ['M54','Dorsalgia',[['M54.0','Paniculitis cervical'],['M54.1','Radiculopatía'],['M54.2','Neuralgia cervical'],['M54.3','Ciática'],['M54.4','Lumbago con ciática'],['M54.5','Dolor lumbar bajo'],['M54.6','Dolor columna torácica'],['M54.8','Otras dorsalgias'],['M54.9','Dorsalgia no especificada']]],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M60','Miositis'],['M61','Calcificación y osificación muscular'],['M62','Otros trastornos musculares'],
            ['M63','Trastornos musculares en enfermedades clasificadas'],['M65','Sinovitis y tenosinovitis'],
            ['M66','Rotura espontánea sinovial y tendón'],['M67','Otros trastornos sinoviales y tendinosos'],
            ['M70','Trastornos tejidos blandos por uso excesivo'],['M71','Otras bursopatías'],
            ['M72','Trastornos fibroblásticos'],['M73','Trastornos tejidos blandos en enfermedades clasificadas'],
            ['M75','Lesiones del hombro'],['M76','Entesopatías miembro inferior'],['M77','Otras entesopatías'],
            ['M79','Otros trastornos tejidos blandos no clasificados'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M80','Osteoporosis con fractura patológica'],['M81','Osteoporosis sin fractura patológica'],
            ['M82','Osteoporosis en enfermedades clasificadas'],['M83','Osteomalacia del adulto'],
            ['M84','Trastornos continuidad del hueso'],['M85','Otros trastornos densidad y estructura ósea'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M86','Osteomielitis'],['M87','Osteonecrosis'],['M88','Enfermedad de Paget del hueso'],
            ['M89','Otros trastornos del hueso'],['M90','Osteopatías en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M91','Osteocondrosis juvenil cadera y pelvis'],['M92','Otras osteocondrosis juveniles'],
            ['M93','Otras osteocondropatías'],['M94','Otros trastornos del cartílago'],
        ]);

        $this->addCodes($codes, 'XIII', 'Enfermedades del sistema musculoesquelético y del tejido conjuntivo', [
            ['M95','Otras deformidades adquiridas musculoesqueléticas'],['M96','Trastornos postprocedimiento musculoesquelético'],
            ['M99','Lesiones biomecánicas no clasificadas'],
        ]);

        // CAPÍTULO XIV: Sistema genitourinario
        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N00','Síndrome nefrítico agudo'],['N01','Síndrome nefrótico recurrente y persistente'],
            ['N02','Hematuria recurrente y persistente'],['N03','Síndrome nefrítico crónico'],
            ['N04','Síndrome nefrótico'],['N05','Síndrome nefrítico no especificado'],
            ['N06','Proteinuria aislada'],['N07','Nefropatía hereditaria'],['N08','Glomerulares en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N10','Nefritis tubulointersticial aguda'],['N11','Nefritis tubulointersticial crónica'],
            ['N12','Nefritis tubulointersticial no especificada'],['N13','Uropatía obstructiva y por reflujo'],
            ['N14','Afecciones renales por drogas'],['N15','Otras renales tubulointersticiales'],
            ['N16','Renales tubulointersticiales en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N17','Insuficiencia renal aguda'],
            ['N18','Insuficiencia renal crónica',[['N18.1','ERC estadio 1'],['N18.2','ERC estadio 2'],['N18.3','ERC estadio 3'],['N18.4','ERC estadio 4'],['N18.5','ERC estadio 5'],['N18.9','ERC no especificada']]],
            ['N19','Insuficiencia renal no especificada'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N20','Cálculo renal y ureteral'],['N21','Cálculo tracto urinario inferior'],
            ['N22','Cálculo urinario en enfermedades clasificadas'],['N23','Cólico renal no especificado'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N25','Trastornos función tubular renal'],['N26','Riñón esclerosado no especificado'],
            ['N27','Riñón pequeño causa desconocida'],['N28','Otros trastornos renales y ureterales'],
            ['N29','Renales en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N30','Cistitis',[['N30.0','Cistitis aguda'],['N30.1','Cistitis intersticial'],['N30.2','Cistitis crónica'],['N30.3','Trigonitis'],['N30.4','Cistitis por radiación'],['N30.8','Otras cistitis'],['N30.9','Cistitis no especificada']]],
            ['N31','Disfunción neuromuscular vejiga'],['N32','Otros trastornos de la vejiga'],
            ['N33','Trastornos vejiga en enfermedades clasificadas'],['N35','Estenosis uretral'],
            ['N36','Otros trastornos de la uretra'],['N37','Uretrales en enfermedades clasificadas'],
            ['N39','Otros trastornos urinarios',[['N39.0','ITU sitio no especificado'],['N39.1','Proteinuria persistente'],['N39.2','Proteinuria ortostática'],['N39.3','Incontinencia urinaria esfuerzo'],['N39.4','Otros tipos incontinencia'],['N39.8','Otros urinarios'],['N39.9','Urinario no especificado']]],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N40','Hiperplasia prostática'],['N41','Enfermedades inflamatorias de la próstata'],
            ['N42','Otros trastornos de la próstata'],['N43','Hidrocele'],['N44','Torsión testicular'],
            ['N45','Orquitis y epididimitis'],['N46','Esterilidad masculina'],['N47','Fimosis y parafimosis'],
            ['N48','Otros trastornos del pene'],['N49','Inflamatorios genitales masculinos'],
            ['N50','Otros trastornos genitales masculinos'],['N51','Genitales masculinos en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N60','Displasias mamarias benignas'],['N61','Trastornos inflamatorios de la mama'],
            ['N62','Hipertrofia de la mama'],['N63','Masa no especificada en la mama'],
            ['N64','Otros trastornos de la mama'],['N65','Mama en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N70','Salpingitis y ooforitis'],['N71','Enfermedad inflamatoria del útero'],
            ['N72','Enfermedad inflamatoria del cérvix'],['N73','Otras pélvicas inflamatorias femeninas'],
            ['N74','Pélvicas inflamatorias en enfermedades clasificadas'],['N75','Enfermedades glándula Bartholin'],
            ['N76','Otras infecciones vagina y vulva'],['N77','Vulvovaginitis en enfermedades clasificadas'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N80','Endometriosis'],['N81','Prolapso genital femenino'],['N82','Fístulas tracto genital femenino'],
            ['N83','Trastornos no inflamatorios ovario y trompa'],['N84','Pólipo tracto genital femenino'],
            ['N85','Otros no inflamatorios del útero'],['N86','Erosión y ectropión del cérvix'],
            ['N87','Displasia del cuello del útero'],['N88','Otros no inflamatorios del cérvix'],
            ['N89','Otros no inflamatorios de la vagina'],['N90','Otros no inflamatorios vulva y perineo'],
            ['N91','Amenorrea oligomenorrea y menstruación escasa'],['N92','Menstruación excesiva frecuente e irregular'],
            ['N93','Otros sangrados uterinos anormales'],['N94','Dolor y otras afecciones genitales femeninos'],
            ['N95','Trastornos menopausia y climaterio'],['N96','Aborto habitual'],
            ['N97','Esterilidad femenina'],['N98','Complicaciones reproducción asistida'],
        ]);

        $this->addCodes($codes, 'XIV', 'Enfermedades del sistema genitourinario', [
            ['N99','Trastornos postprocedimiento genitourinario'],
        ]);


        // CAPÍTULO XV: Embarazo parto y puerperio
        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O00','Embarazo ectópico'],['O01','Mola hidatiforme'],['O02','Otros productos anormales'],
            ['O03','Aborto espontáneo'],['O04','Aborto médico'],['O05','Otro aborto'],['O06','Aborto no especificado'],
            ['O07','Intento abortivo fallido'],['O08','Complicaciones consecutivas al aborto'],
        ]);

        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O10','HTA preexistente complicando embarazo',[['O10.0','HTA esencial preexistente'],['O10.1','HTA cardíaca preexistente'],['O10.2','HTA renal preexistente'],['O10.3','HTA cardiorrenal preexistente'],['O10.4','HTA secundaria preexistente'],['O10.9','HTA preexistente no especificada']]],
            ['O11','HTA preexistente con proteinuria añadida'],
            ['O12','Edema y proteinuria sin hipertensión',[['O12.0','Edema gestacional'],['O12.1','Proteinuria gestacional'],['O12.2','Edema con proteinuria gestacional']]],
            ['O13','HTA gestacional sin proteinuria'],['O14','Preeclampsia',[['O14.0','Preeclampsia moderada'],['O14.1','Preeclampsia severa'],['O14.9','Preeclampsia no especificada']]],
            ['O15','Eclampsia',[['O15.0','Eclampsia en el embarazo'],['O15.1','Eclampsia en el parto'],['O15.2','Eclampsia en el puerperio'],['O15.9','Eclampsia no especificada']]],
            ['O16','HTA materna no especificada'],
        ]);

        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O20','Hemorragia del embarazo temprano'],['O21','Vómitos excesivos en el embarazo'],
            ['O22','Complicaciones venosas en el embarazo'],['O23','Infecciones genitourinarias en el embarazo'],
            ['O24','Diabetes mellitus en el embarazo'],['O25','Desnutrición en el embarazo'],
            ['O26','Atención por otras complicaciones del embarazo'],['O28','Hallazgos anormales screening antenatal'],
            ['O29','Complicaciones de anestesia en el embarazo'],
        ]);

        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O30','Embarazo múltiple'],['O31','Complicaciones específicas del embarazo múltiple'],
            ['O32','Presentación anormal del feto'],['O33','Desproporción'],['O34','Cicatriz uterina previa'],
            ['O35','Anomalías fetales'],['O36','Otros problemas fetales'],['O40','Polihidramnios'],
            ['O41','Trastornos líquido amniótico'],['O42','Rotura prematura de membranas'],
            ['O43','Trastornos de la placenta'],['O44','Placenta previa'],['O45','Desprendimiento prematuro de placenta'],
            ['O46','Hemorragia anteparto no clasificada'],['O47','Falso trabajo de parto'],
            ['O48','Embarazo prolongado'],
        ]);

        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O60','Parto prematuro',[['O60.0','Trabajo de parto pretérmino sin parto'],['O60.1','Parto pretérmino espontáneo'],['O60.2','Parto pretérmino por inducción'],['O60.3','Parto pretérmino no especificado']]],
            ['O61','Inducción fallida',[['O61.0','Inducción médica fallida'],['O61.1','Inducción instrumental fallida'],['O61.8','Otras inducciones fallidas'],['O61.9','Inducción fallida no especificada']]],
            ['O62','Distocia',[['O62.0','Contracciones insuficientes'],['O62.1','Contracciones incoordinadas'],['O62.2','Otras distocias'],['O62.3','Trabajo parto precipitado'],['O62.4','Hipertónica uterina'],['O62.8','Otras distocias'],['O62.9','Distocia no especificada']]],
            ['O63','Trabajo de parto prolongado',[['O63.0','Primera etapa prolongada'],['O63.1','Segunda etapa prolongada'],['O63.2','Parto retenido'],['O63.9','Prolongado no especificado']]],
            ['O64','Desproporción',[['O64.0','Rotación incompleta'],['O64.1','Presentación de frente'],['O64.2','Presentación de cara'],['O64.3','Presentación de nalgas'],['O64.4','Presentación de hombro'],['O64.5','Presentación compuesta'],['O64.8','Otra desproporción'],['O64.9','Desproporción no especificada']]],
            ['O65','Anomalía pélvica',[['O65.0','Pelvis contraída'],['O65.1','Pelvis deformada'],['O65.2','Otras anomalías pélvicas'],['O65.3','Prolapso útero gestante'],['O65.4','Otras anomalías'],['O65.9','Anomalía no especificada']]],
            ['O66','Otra obstrucción',[['O66.0','Parto hombro distocia'],['O66.1','Gemelos retenidos'],['O66.2','Feto grande'],['O66.3','Otras anomalías fetales'],['O66.4','Otra obstrucción no especificada']]],
            ['O67','Hemorragia intraparto'],['O68','Sufrimiento fetal',[['O68.0','Sufrimiento fetal con alteración FC'],['O68.1','Sufrimiento fetal con meconio'],['O68.2','Sufrimiento fetal con alteración bioquímica'],['O68.3','Sufrimiento fetal con alteración ECG'],['O68.8','Otros signos sufrimiento fetal'],['O68.9','Sufrimiento fetal no especificado']]],
            ['O69','Complicaciones del cordón umbilical'],['O70','Desgarro perineal durante el parto'],
            ['O71','Otros traumatismos del parto'],['O72','Hemorragia postparto'],
            ['O73','Retención de placenta'],['O74','Complicaciones de anestesia durante el parto'],
            ['O75','Otras complicaciones del parto'],
        ]);

        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O80','Parto único espontáneo'],['O81','Parto con fórceps'],['O82','Parto por cesárea'],
            ['O83','Otros partos con maniobras'],['O84','Parto múltiple'],
        ]);

        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O85','Infección puerperal'],['O86','Otras infecciones puerperales'],['O87','Embolia venosa puerperal'],
            ['O88','Embolia obstétrica'],['O89','Complicaciones de anestesia puerperal'],
            ['O90','Otras complicaciones del puerperio'],['O91','Infecciones de la mama puerperales'],
            ['O92','Otros trastornos de la mama puerperales'],
        ]);

        $this->addCodes($codes, 'XV', 'Embarazo parto y puerperio', [
            ['O95','Muerte obstétrica de causa no especificada'],['O96','Muerte obstétrica tardía'],
            ['O97','Muerte por secuelas obstétricas'],['O98','Enfermedades maternas infecciosas en el embarazo'],
            ['O99','Otras enfermedades maternas en el embarazo'],
        ]);

        // CAPÍTULO XVI: Perinatal
        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P00','Feto afectado por condiciones maternas'],['P01','Feto afectado por complicaciones placentarias'],
            ['P02','Feto afectado por complicaciones del cordón'],['P03','Feto afectado por otras complicaciones del parto'],
            ['P04','Feto afectado por sustancias maternas'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P05','Crecimiento fetal lento e insuficiencia'],['P07','Trastornos relacionados con gestación corta'],
            ['P08','Trastornos relacionados con gestación prolongada'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P10','Lesión intracraneal por trauma de parto'],['P11','Otras lesiones SNC por trauma de parto'],
            ['P12','Lesión cuero cabelludo por trauma de parto'],['P13','Lesión esqueleto por trauma de parto'],
            ['P14','Lesión nervios periféricos por trauma de parto'],['P15','Otras lesiones por trauma de parto'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P20','Hipoxia intrauterina'],['P21','Asfixia al nacer'],['P22','Dificultad respiratoria del recién nacido'],
            ['P23','Neumonía congénita'],['P24','Síndrome de aspiración neonatal'],
            ['P25','Enfisema intersticial perinatal'],['P26','Hemorragia pulmonar perinatal'],
            ['P27','Enfermedad respiratoria crónica perinatal'],['P28','Otros trastornos respiratorios perinatales'],
            ['P29','Trastornos cardiovasculares perinatales'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P35','Enfermedades virales congénitas'],['P36','Sepsis bacteriana del recién nacido'],
            ['P37','Otras enfermedades infecciosas perinatales'],['P38','Onfalitis del recién nacido'],
            ['P39','Otras infecciones perinatales'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P50','Hemorragia fetal'],['P51','Hemorragia umbilical del recién nacido'],
            ['P52','Hemorragia intracraneal no traumática del feto y RN'],['P53','Hemorragia fetal no especificada'],
            ['P54','Otras hemorragias perinatales'],['P55','Enfermedad hemolítica del feto y RN'],
            ['P56','Hidropesía fetal por enfermedad hemolítica'],['P57','Kernicterus'],
            ['P58','Ictericia neonatal por hemólisis'],['P59','Ictericia neonatal por otras causas'],
            ['P60','CID fetal y neonatal'],['P61','Otros trastornos hematológicos perinatales'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P70','Trastornos de glucosa neonatal'],['P71','Deficiencias calcio y magnesio neonatales'],
            ['P72','Otros trastornos endocrinos neonatales'],['P74','Alteraciones electrolíticas neonatales'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P75','Íleo meconial'],['P76','Obstrucción intestinal neonatal'],['P77','Enterocolitis necrotizante'],
            ['P78','Otras alteraciones digestivas perinatales'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P80','Hipotermia neonatal'],['P81','Otros trastornos termorregulación neonatal'],
            ['P83','Otras alteraciones superficiales neonatales'],
        ]);

        $this->addCodes($codes, 'XVI', 'Ciertas afecciones originadas en el período perinatal', [
            ['P90','Convulsiones neonatales'],['P91','Otras alteraciones SNC neonatal'],
            ['P92','Problemas de alimentación neonatal'],['P93','Reacciones a drogas en feto y RN'],
            ['P94','Hipotonia neonatal'],['P95','Muerte fetal'],['P96','Otras afecciones perinatales'],
        ]);

        // CAPÍTULO XVII: Malformaciones congénitas
        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q00','Anencefalia y malformaciones similares'],['Q01','Encefalocele'],['Q02','Microcefalia'],
            ['Q03','Hidrocefalia congénita'],['Q04','Otras malformaciones cerebrales congénitas'],
            ['Q05','Espina bífida'],['Q06','Otras malformaciones de la médula espinal'],
            ['Q07','Otras malformaciones del SNC'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q10','Malformaciones congénitas de párpados'],['Q11','Anoftalmía y microftalmía'],
            ['Q12','Malformaciones congénitas del cristalino'],['Q13','Malformaciones congénitas segmento anterior del ojo'],
            ['Q14','Malformaciones congénitas segmento posterior del ojo'],['Q15','Otras malformaciones congénitas del ojo'],
            ['Q16','Malformaciones congénitas del oído'],['Q17','Otras malformaciones congénitas de la oreja'],
            ['Q18','Otras malformaciones congénitas cara y cuello'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q20','Malformaciones congénitas de cavidades cardíacas'],['Q21','Malformaciones congénitas tabiques cardíacos'],
            ['Q22','Malformaciones congénitas válvula pulmonar y tricúspide'],
            ['Q23','Malformaciones congénitas válvula aórtica y mitral'],['Q24','Otras malformaciones cardíacas congénitas'],
            ['Q25','Malformaciones congénitas grandes arterias'],['Q26','Malformaciones congénitas grandes venas'],
            ['Q27','Otras malformaciones congénitas vasculares periféricas'],
            ['Q28','Malformaciones congénitas vasos cerebrovasculares'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q30','Malformaciones congénitas de la nariz'],['Q31','Malformaciones congénitas de la laringe'],
            ['Q32','Malformaciones congénitas tráquea y bronquios'],['Q33','Malformaciones congénitas del pulmón'],
            ['Q34','Otras malformaciones congénitas del sistema respiratorio'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q35','Paladar hendido'],['Q36','Labio hendido'],['Q37','Paladar y labio hendidos'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q38','Malformaciones lengua y boca'],['Q39','Malformaciones del esófago'],
            ['Q40','Malformaciones del estómago'],['Q41','Malformaciones intestino delgado'],
            ['Q42','Malformaciones colon'],['Q43','Otras malformaciones intestinales'],
            ['Q44','Malformaciones vesícula biliar e hígado'],['Q45','Malformaciones páncreas y vías pancreáticas'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q50','Malformaciones ovario y trompa'],['Q51','Malformaciones útero y cuello uterino'],
            ['Q52','Otras malformaciones genitales femeninas'],['Q53','Testículo no descendido'],
            ['Q54','Hipospadias'],['Q55','Otras malformaciones genitales masculinas'],['Q56','Sexo indeterminado'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q60','Agenesia renal'],['Q61','Riñón quístico'],['Q62','Malformaciones obstructivas pelvis renal y uréter'],
            ['Q63','Otras malformaciones del riñón'],['Q64','Otras malformaciones vejiga y uretra'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q65','Malformaciones congénitas de la cadera'],['Q66','Malformaciones congénitas del pie'],
            ['Q67','Malformaciones cráneo cara y columna'],['Q68','Otras malformaciones osteomusculares'],
            ['Q69','Polidactilia'],['Q70','Sindactilia'],['Q71','Defectos por reducción miembro superior'],
            ['Q72','Defectos por reducción miembro inferior'],['Q73','Defectos por reducción miembro no especificado'],
            ['Q74','Otras malformaciones de los miembros'],['Q75','Malformaciones cráneo y cara'],
            ['Q76','Malformaciones de la columna vertebral'],['Q77','Osteocondrodisplasia'],
            ['Q78','Otras osteodisplasias'],['Q79','Malformaciones musculoesqueléticas no clasificadas'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q80','Ictiosis congénita'],['Q81','Epidermólisis bullosa'],
            ['Q82','Otras malformaciones cutáneas'],['Q83','Malformaciones de la mama'],
            ['Q84','Otras malformaciones tegumentarias'],['Q85','Facomatosis no clasificadas en otra parte'],
            ['Q86','Síndromes malformativos congénitos por causas ambientales'],
            ['Q87','Síndromes malformativos congénitos genéticos'],['Q89','Otras malformaciones congénitas'],
        ]);

        $this->addCodes($codes, 'XVII', 'Malformaciones congénitas deformidades y anomalías cromosómicas', [
            ['Q90','Síndrome de Down'],['Q91','Trisomía 18 y trisomía 13'],
            ['Q92','Otras trisomías y trisomías parciales'],['Q93','Monosomías y deleciones'],
            ['Q95','Rearreglos balanceados'],['Q96','Síndrome de Turner'],['Q97','Otras anomalías X'],
            ['Q98','Otras anomalías Y'],['Q99','Otras anomalías cromosómicas'],
        ]);


        // CAPÍTULO XVIII: Síntomas y hallazgos anormales
        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R00','Anomalías del latido cardíaco'],['R01','Soplos cardíacos'],['R02','Gangrena no clasificada'],
            ['R03','Lectura anormal de presión arterial'],['R04','Hemorragia de vías respiratorias'],['R05','Tos'],
            ['R06','Anomalías de la respiración'],['R07','Dolor de garganta y pecho'],
            ['R09','Otros síntomas respiratorios y circulatorios'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R10','Dolor abdominal',[['R10.0','Abdomen agudo'],['R10.1','Dolor abdominal localizado'],['R10.2','Dolor pélvico'],['R10.3','Dolor abdominal generalizado'],['R10.4','Otros dolores abdominales']]],
            ['R11','Náusea y vómito'],['R12','Acidez'],['R13','Disfagia'],['R14','Flatulencia y trastornos afines'],
            ['R15','Incontinencia fecal'],['R16','Hepatomegalia y esplenomegalia'],['R17','Ictericia no especificada'],
            ['R18','Ascitis'],['R19','Otros síntomas digestivos'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R20','Alteraciones de la sensibilidad cutánea'],['R21','Erupción cutánea no especificada'],
            ['R22','Tumoración localizada'],['R23','Otros cambios cutáneos'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R25','Movimientos involuntarios anormales'],['R26','Anomalías de la marcha'],
            ['R27','Otros trastornos de la coordinación'],['R29','Otros síntomas neurológicos'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R30','Dolor urinario'],['R31','Hematuria no especificada'],['R32','Incontinencia urinaria no especificada'],
            ['R33','Retención de orina'],['R34','Anuria y oliguria'],['R35','Poliuria'],
            ['R36','Secreción uretral'],['R39','Otros síntomas urinarios'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R40','Somnolencia estupor y coma'],['R41','Síntomas cognición y conciencia'],
            ['R42','Mareo y desequilibrio'],['R43','Alteraciones olfato y gusto'],
            ['R44','Alucinaciones'],['R45','Síntomas emocionales'],['R46','Apariencia y comportamiento'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R47','Trastornos del lenguaje'],['R48','Trastornos lectura y escritura'],['R49','Trastornos de la voz'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R50','Fiebre',[['R50.2','Fiebre inducida por drogas'],['R50.8','Otras fiebres especificadas'],['R50.9','Fiebre no especificada']]],
            ['R51','Cefalea',[['R51.0','Cefalea']],['R52','Dolor no clasificado en otra parte']],
            ['R53','Malestar y fatiga'],['R54','Senilidad'],['R55','Síncope y colapso',[['R55.0','Síncope']]],
            ['R56','Convulsiones no clasificadas'],['R57','Shock no clasificado en otra parte'],
            ['R58','Hemorragia no clasificada'],['R59','Adenomegalia'],['R60','Edema no clasificado'],
            ['R61','Hiperpilosidad'],['R62','Falta desarrollo esperado'],['R63','Síntomas alimentación'],
            ['R64','Caquexia'],['R65','SIRS'],['R68','Otros síntomas generales'],['R69','Causa desconocida'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R70','Eritrosedimentación elevada'],['R71','Anomalías eritrocitos'],['R72','Anomalías leucocitos'],
            ['R73','Glucosa elevada'],['R74','Enzimas séricas anormales'],['R75','Examen VIH'],
            ['R76','Serología anormal'],['R77','Proteínas plasmáticas anormales'],
            ['R78','Hallazgos drogas en sangre'],['R79','Otros hallazgos bioquímicos'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R80','Proteinuria aislada'],['R81','Glucosuria'],['R82','Otros hallazgos orina'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R83','Hallazgos LCR'],['R84','Hallazgos muestras respiratorias'],['R85','Hallazgos muestras digestivas'],
            ['R86','Hallazgos genitales masculinos'],['R87','Hallazgos genitales femeninos'],
            ['R89','Hallazgos otros líquidos y tejidos'],
        ]);

        $this->addCodes($codes, 'XVIII', 'Síntomas signos y hallazgos anormales clínicos y de laboratorio no clasificados en otra parte', [
            ['R90','Hallazgos SNC imagen'],['R91','Hallazgos pulmón imagen'],['R92','Hallazgos mama imagen'],
            ['R93','Hallazgos otros órganos imagen'],['R94','Pruebas funcionales anormales'],
            ['R95','Síndrome muerte súbita infantil'],['R96','Muerte súbita adulto'],
            ['R98','Muerte sin testigo'],['R99','Otras causas mal definidas'],
        ]);

        // CAPÍTULO XIX: Traumatismos y envenenamientos
        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S00','Traumatismo superficial cabeza'],['S01','Herida cabeza'],['S02','Fractura cráneo y huesos cara'],
            ['S03','Luxación cabeza'],['S04','Nervios craneales'],['S05','Ojo y órbita'],
            ['S06','Traumatismo intracraneal',[['S06.0','Conmoción cerebral'],['S06.1','Edema cerebral'],['S06.2','Contusión cerebral'],['S06.3','Hematoma extradural'],['S06.4','Hematoma epidural'],['S06.5','Hematoma subdural'],['S06.6','Hemorragia subaracnoidea'],['S06.7','Lesión intracraneal múltiple'],['S06.8','Otras lesiones'],['S06.9','Intracraneal no especificada']]],
            ['S07','Aplastamiento cabeza'],['S08','Amputación cabeza'],['S09','Otros traumatismos cabeza'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S10','Traumatismo superficial cuello'],['S11','Herida cuello'],['S12','Fractura cervical'],
            ['S13','Luxación cervical'],['S14','Médula cervical'],['S15','Vasos cuello'],
            ['S16','Tendón cuello'],['S17','Aplastamiento cuello'],['S18','Amputación cuello'],['S19','Otros cuello'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S20','Traumatismo superficial tórax'],['S21','Herida tórax'],['S22','Fracturas tórax',[['S22.0','Fractura vértebra torácica'],['S22.1','Fractura esternón'],['S22.2','Fractura costilla'],['S22.3','Fractura múltiple costillas'],['S22.4','Tórax inestable'],['S22.5','Otras fracturas tórax']]],
            ['S23','Luxación tórax'],['S24','Médula torácica'],['S25','Vasos tórax'],
            ['S26','Traumatismo corazón'],['S27','Otros intratorácicos'],['S28','Aplastamiento tórax'],['S29','Otros tórax'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S30','Traumatismo superficial abdomen'],['S31','Herida abdomen'],['S32','Fractura lumbar y pelvis',[['S32.0','Fractura vértebra lumbar'],['S32.1','Fractura sacro'],['S32.2','Fractura cóccix'],['S32.3','Fractura ilion'],['S32.4','Fractura acetábulo'],['S32.5','Fractura pubis'],['S32.7','Fractura múltiple'],['S32.8','Otras fracturas']]],
            ['S33','Luxación lumbar y pelvis'],['S34','Médula lumbar'],['S35','Vasos abdomen'],
            ['S36','Órganos intraabdominales'],['S37','Órganos urinarios'],['S38','Aplastamiento abdomen'],['S39','Otros abdomen'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S40','Traumatismo superficial hombro'],['S41','Herida hombro'],
            ['S42','Fractura húmero',[['S42.0','Fractura clavícula'],['S42.1','Fractura escápula'],['S42.2','Fractura extremo superior húmero'],['S42.3','Fractura diáfisis húmero'],['S42.4','Fractura extremo inferior húmero'],['S42.7','Fractura múltiple'],['S42.8','Otras fracturas hombro']]],
            ['S43','Luxación hombro'],['S44','Nervios hombro'],['S45','Vasos hombro'],['S46','Tendón hombro'],
            ['S47','Aplastamiento hombro'],['S48','Amputación hombro'],['S49','Otros hombro'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S50','Traumatismo superficial codo'],['S51','Herida codo'],
            ['S52','Fractura antebrazo',[['S52.0','Fractura extremo superior cúbito'],['S52.1','Fractura extremo superior radio'],['S52.2','Fractura diáfisis cúbito'],['S52.3','Fractura diáfisis radio'],['S52.4','Fractura diáfisis ambos huesos'],['S52.5','Fractura extremo inferior radio'],['S52.6','Fractura extremo inferior cúbito'],['S52.7','Fractura múltiple'],['S52.8','Otras fracturas antebrazo']]],
            ['S53','Luxación codo'],['S54','Nervios codo'],['S55','Vasos codo'],['S56','Tendón codo'],
            ['S57','Aplastamiento codo'],['S58','Amputación antebrazo'],['S59','Otros codo'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S60','Traumatismo superficial muñeca'],['S61','Herida muñeca y mano'],
            ['S62','Fractura muñeca y mano',[['S62.0','Fractura escafoides'],['S62.1','Fractura otros huesos carpo'],['S62.2','Fractura primer metacarpiano'],['S62.3','Fractura otros metacarpianos'],['S62.4','Fractura múltiple metacarpianos'],['S62.5','Fractura dedo'],['S62.6','Fractura múltiple dedos'],['S62.7','Otras fracturas muñeca y mano']]],
            ['S63','Luxación muñeca y mano'],['S64','Nervios muñeca y mano'],['S65','Vasos muñeca y mano'],
            ['S66','Tendón muñeca y mano'],['S67','Aplastamiento muñeca'],['S68','Amputación muñeca y mano'],['S69','Otros muñeca'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S70','Traumatismo superficial cadera'],['S71','Herida cadera'],
            ['S72','Fractura fémur',[['S72.0','Fractura cuello femoral'],['S72.1','Fractura pertrocantérea'],['S72.2','Fractura subtrocantérea'],['S72.3','Fractura diáfisis femoral'],['S72.4','Fractura extremo inferior fémur'],['S72.7','Fractura múltiple fémur'],['S72.8','Otras fracturas fémur'],['S72.9','Fractura fémur no especificada']]],
            ['S73','Luxación cadera'],['S74','Nervios cadera'],['S75','Vasos cadera'],['S76','Tendón cadera'],
            ['S77','Aplastamiento cadera'],['S78','Amputación cadera'],['S79','Otros cadera'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S80','Traumatismo superficial rodilla'],['S81','Herida rodilla'],
            ['S82','Fractura pierna',[['S82.0','Fractura rótula'],['S82.1','Fractura extremo superior tibia'],['S82.2','Fractura diáfisis tibia'],['S82.3','Fractura extremo inferior tibia'],['S82.4','Fractura peroné sola'],['S82.5','Fractura maléolo interno'],['S82.6','Fractura maléolo externo'],['S82.7','Fractura múltiple pierna'],['S82.8','Otras fracturas pierna']]],
            ['S83','Luxación rodilla'],['S84','Nervios pierna'],['S85','Vasos pierna'],['S86','Tendón pierna'],
            ['S87','Aplastamiento pierna'],['S88','Amputación pierna'],['S89','Otros pierna'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['S90','Traumatismo superficial tobillo'],['S91','Herida tobillo'],
            ['S92','Fractura pie',[['S92.0','Fractura calcáneo'],['S92.1','Fractura astrágalo'],['S92.2','Fractura otros tarsianos'],['S92.3','Fractura metatarsiano'],['S92.4','Fractura dedo pie'],['S92.5','Fractura múltiple pie'],['S92.7','Otras fracturas pie']]],
            ['S93','Luxación tobillo y pie'],['S94','Nervios tobillo'],['S95','Vasos tobillo'],['S96','Tendón tobillo'],
            ['S97','Aplastamiento pie'],['S98','Amputación pie'],['S99','Otros tobillo y pie'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T00','Traumatismos superficiales múltiples'],['T01','Heridas múltiples'],
            ['T02','Fracturas múltiples'],['T03','Luxaciones múltiples'],['T04','Aplastamiento múltiple'],
            ['T05','Amputaciones múltiples'],['T06','Otros múltiples'],['T07','Múltiples no especificadas'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T08','Fractura columna vertebral nivel no especificado'],['T09','Otros traumatismos columna vertebral'],
            ['T10','Fractura miembro superior nivel no especificado'],['T11','Otros traumatismos miembro superior'],
            ['T12','Fractura miembro inferior nivel no especificado'],['T13','Otros traumatismos miembro inferior'],
            ['T14','Traumatismo no especificado',[['T14.0','Traumatismo superficial no especificado'],['T14.1','Herida no especificada'],['T14.2','Fractura no especificada'],['T14.3','Luxación no especificada'],['T14.4','Lesión nerviosa no especificada'],['T14.5','Lesión vascular no especificada'],['T14.6','Lesión tendón no especificada'],['T14.7','Aplastamiento no especificado'],['T14.8','Amputación no especificada'],['T14.9','Otros traumatismos no especificados']]],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T15','Cuerpo extraño en ojo externo'],['T16','Cuerpo extraño en oído'],['T17','Cuerpo extraño vías respiratorias'],
            ['T18','Cuerpo extraño digestivo'],['T19','Cuerpo extraño genitourinario'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T20','Quemadura cabeza y cuello'],['T21','Quemadura tronco'],['T22','Quemadura hombro y brazo'],
            ['T23','Quemadura muñeca y mano'],['T24','Quemadura cadera y pierna'],['T25','Quemadura tobillo y pie'],
            ['T26','Quemadura ojo'],['T27','Quemadura vías respiratorias'],['T28','Quemadura otros órganos internos'],
            ['T29','Quemaduras múltiples'],['T30','Quemadura no especificada'],['T31','Quemaduras según extensión'],
            ['T32','Quemaduras según extensión'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T33','Congelación superficial'],['T34','Congelación con necrosis'],['T35','Congelación múltiple'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T36','Envenenamiento por antibióticos'],['T37','Envenenamiento por otros antiinfecciosos'],
            ['T38','Envenenamiento por hormonas'],['T39','Envenenamiento por analgésicos'],
            ['T40','Envenenamiento por narcóticos'],['T41','Envenenamiento por anestésicos'],
            ['T42','Envenenamiento por antiepilépticos'],['T43','Envenenamiento por psicotrópicos'],
            ['T44','Envenenamiento por autonómicos'],['T45','Envenenamiento por sanguíneos'],
            ['T46','Envenenamiento por cardiovasculares'],['T47','Envenenamiento por digestivos'],
            ['T48','Envenenamiento por aparato respiratorio'],['T49','Envenenamiento por tópicos'],
            ['T50','Envenenamiento por diuréticos y otros'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T51','Efecto tóxico del alcohol'],['T52','Efecto tóxico disolventes orgánicos'],
            ['T53','Efecto tóxico hidrocarburos'],['T54','Efecto tóxico corrosivos'],['T55','Efecto tóxico jabones'],
            ['T56','Efecto tóxico metales'],['T57','Efecto tóxico alimentos'],['T58','Efecto tóxico monóxido carbono'],
            ['T59','Efecto tóxico gases'],['T60','Efecto tóxico pesticidas'],['T61','Efecto tóxico toxinas marinas'],
            ['T62','Efecto tóxico toxinas ingeridas'],['T63','Efecto tóxico mordeduras y picaduras'],
            ['T64','Efecto tóxico contaminantes'],['T65','Efecto tóxico otros'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T66','Efecto radiación no especificado'],['T67','Efecto calor y luz'],
            ['T68','Hipotermia no asociada con baja temperatura'],['T69','Otros efectos frío'],
            ['T70','Efectos presión atmosférica'],['T71','Asfixia traumática'],['T73','Efectos privación'],
            ['T74','Síndromes maltrato'],['T75','Efectos otras causas externas'],
            ['T78','Efectos adversos no clasificados',[['T78.0','Shock anafiláctico'],['T78.1','Otras reacciones adversas alimentos'],['T78.2','Shock alérgico no especificado'],['T78.3','Edema angioneurótico'],['T78.4','Alergia no especificada'],['T78.8','Otros efectos adversos'],['T78.9','Efecto adverso no especificado']]],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T79','Complicaciones traumáticas'],
        ]);

        $this->addCodes($codes, 'XIX', 'Traumatismos y envenenamientos y algunas otras consecuencias de causas externas', [
            ['T80','Complicaciones infusión transfusión e inyección'],['T81','Complicaciones procedimientos no clasificadas'],
            ['T82','Complicaciones prótesis cardíaca y vascular'],['T83','Complicaciones prótesis genitourinaria'],
            ['T84','Complicaciones prótesis ortopédica'],['T85','Complicaciones otras prótesis'],
            ['T86','Fracaso y rechazo trasplante'],['T87','Complicaciones amputación'],
            ['T88','Otras complicaciones quirúrgicas'],
        ]);


        // CAPÍTULO XX: Causas externas
        $this->addCodes($codes, 'XX', 'Causas externas de morbilidad y mortalidad', [
            ['V01-V09','Peatón lesionado en accidente de transporte'],
            ['V10-V19','Ciclista lesionado en accidente de transporte'],
            ['V20-V29','Motociclista lesionado en accidente de transporte'],
            ['V30-V39','Ocupante vehículo 3 ruedas lesionado en accidente'],
            ['V40-V49','Ocupante automóvil lesionado en accidente'],
            ['V50-V59','Ocupante camioneta lesionado en accidente'],
            ['V60-V69','Ocupante vehículo pesado lesionado en accidente'],
            ['V70-V79','Ocupante autobús lesionado en accidente'],
            ['V80-V89','Otros accidentes de transporte terrestre'],
            ['V90-V94','Accidentes de transporte acuático'],
            ['V95-V97','Accidentes de transporte aéreo'],
            ['V98-V99','Otros accidentes de transporte no clasificados'],
        ]);

        $this->addCodes($codes, 'XX', 'Causas externas de morbilidad y mortalidad', [
            ['W00-W19','Caídas'],['W20-W49','Exposición a fuerzas mecánicas inanimadas'],
            ['W50-W64','Exposición a fuerzas mecánicas animadas'],['W65-W74','Ahogamiento y sumersión'],
            ['W75-W84','Otras obstrucciones respiratorias'],['W85-W99','Exposición a electricidad radiación y temperatura'],
        ]);

        $this->addCodes($codes, 'XX', 'Causas externas de morbilidad y mortalidad', [
            ['X00-X09','Exposición a fuego y humo'],['X10-X19','Contacto con calor y sustancias calientes'],
            ['X20-X29','Contacto con animales venenosos'],['X30-X39','Exposición a fuerzas naturales'],
            ['X40-X49','Envenenamiento accidental'],['X50-X57','Sobreesfuerzo y privación'],
            ['X58-X59','Accidentes no clasificados'],['X60-X84','Lesión autoinfligida intencionalmente'],
            ['X85-Y09','Agresión'],['Y10-Y34','Eventos intención no determinada'],
            ['Y35-Y36','Intervención legal y guerra'],['Y40-Y84','Complicaciones atención médica y quirúrgica'],
            ['Y85-Y89','Secuelas de causas externas'],['Y90-Y98','Factores suplementarios'],
        ]);

        // CAPÍTULO XXI: Factores que influyen en salud
        $this->addCodes($codes, 'XXI', 'Factores que influyen en el estado de salud y contacto con servicios de salud', [
            ['Z00','Examen médico general'],['Z01','Otros exámenes especiales'],['Z02','Exámenes administrativos'],
            ['Z03','Observación y evaluación médica'],['Z04','Exámenes por otras razones'],
            ['Z08','Seguimiento posterior neoplasia'],['Z09','Seguimiento posterior tratamiento'],
            ['Z12','Screening neoplasia'],['Z13','Screening otros'],
        ]);

        $this->addCodes($codes, 'XXI', 'Factores que influyen en el estado de salud y contacto con servicios de salud', [
            ['Z20','Contacto con enfermedades transmisibles'],['Z21','VIH asintomático'],
            ['Z22','Portador de enfermedades infecciosas'],['Z23','Vacunación bacteriana'],
            ['Z24','Vacunación viral'],['Z25','Vacunación otras virales'],['Z26','Vacunación otras infecciosas'],
            ['Z27','Vacunación combinada'],['Z28','Vacunación no realizada'],['Z29','Otras profilaxis'],
        ]);

        $this->addCodes($codes, 'XXI', 'Factores que influyen en el estado de salud y contacto con servicios de salud', [
            ['Z30','Anticoncepción',[['Z30.0','Consejo anticoncepción'],['Z30.1','Inserción DIU'],['Z30.2','Esterilización'],['Z30.3','Mensual anticoncepción'],['Z30.4','Supervisión anticonceptivos'],['Z30.5','Supervisión DIU'],['Z30.8','Otra anticoncepción'],['Z30.9','Anticoncepción no especificada']]],
            ['Z31','Fertilidad',[['Z31.0','Inversión esterilización'],['Z31.1','Inseminación artificial'],['Z31.2','Fertilización in vitro'],['Z31.3','Otros fertilidad'],['Z31.4','Investigación fertilidad'],['Z31.5','Consejo genético'],['Z31.8','Otros fertilidad'],['Z31.9','Fertilidad no especificada']]],
            ['Z32','Examen embarazo',[['Z32.0','Embarazo confirmado'],['Z32.1','Embarazo no confirmado']]],
            ['Z33','Estado gestacional'],['Z34','Supervisión embarazo normal',[['Z34.0','Supervisión primer embarazo'],['Z34.8','Supervisión otro embarazo'],['Z34.9','Supervisión embarazo no especificado']]],
            ['Z35','Supervisión embarazo alto riesgo',[['Z35.0','Embarazo con infertilidad previa'],['Z35.1','Embarazo con abortos previos'],['Z35.2','Embarazo con otra complicación'],['Z35.3','Embarazo con insuficiente atención'],['Z35.4','Embarazo multigesta'],['Z35.5','Embarazo primigesta mayor'],['Z35.6','Embarazo muy joven'],['Z35.7','Embarazo alto riesgo social'],['Z35.8','Otros alto riesgo'],['Z35.9','Alto riesgo no especificado']]],
            ['Z36','Screening prenatal'],['Z37','Resultado del parto',[['Z37.0','Nacido vivo único'],['Z37.1','Nacido muerto único'],['Z37.2','Gemelos ambos nacidos vivos'],['Z37.3','Gemelos uno nacido muerto'],['Z37.4','Gemelos ambos nacidos muertos'],['Z37.5','Múltiples todos nacidos vivos'],['Z37.6','Múltiples algunos nacidos muertos'],['Z37.7','Múltiples todos nacidos muertos'],['Z37.9','Resultado parto no especificado']]],
            ['Z38','Nacidos vivos según lugar',[['Z38.0','Nacido vivo hospital'],['Z38.1','Nacido vivo fuera hospital'],['Z38.2','Nacido vivo lugar no especificado'],['Z38.3','Gemelo nacido vivo hospital'],['Z38.4','Gemelo nacido vivo fuera'],['Z38.5','Gemelo nacido vivo lugar no especificado'],['Z38.6','Múltiple nacido vivo hospital'],['Z38.7','Múltiple nacido vivo fuera'],['Z38.8','Múltiple nacido vivo lugar no especificado']]],
            ['Z39','Atención postparto',[['Z39.0','Atención inmediata postparto'],['Z39.1','Atención lactancia'],['Z39.2','Atención postparto rutinaria']]],
        ]);

        $this->addCodes($codes, 'XXI', 'Factores que influyen en el estado de salud y contacto con servicios de salud', [
            ['Z40','Cirugía profiláctica'],['Z41','Procedimientos no terapéuticos'],['Z42','Cuidado postratamiento'],
            ['Z43','Atención ostomías'],['Z44','Prótesis externas'],['Z45','Ajuste dispositivos implantados'],
            ['Z46','Otros dispositivos'],['Z47','Seguimiento ortopédico'],['Z48','Seguimiento quirúrgico'],
            ['Z49','Diálisis'],['Z50','Rehabilitación'],['Z51','Otros cuidados médicos'],
            ['Z52','Donantes'],['Z53','Procedimiento no realizado'],['Z54','Convalecencia'],
        ]);

        $this->addCodes($codes, 'XXI', 'Factores que influyen en el estado de salud y contacto con servicios de salud', [
            ['Z55','Alfabetización'],['Z56','Desempleo'],['Z57','Riesgos laborales'],['Z58','Ambiente físico'],
            ['Z59','Vivienda'],['Z60','Aislamiento social'],['Z61','Eventos negativos infancia'],
            ['Z62','Otros infancia'],['Z63','Problemas convivencia'],['Z64','Conductas de riesgo'],
            ['Z65','Otros psicosociales'],
        ]);

        $this->addCodes($codes, 'XXI', 'Factores que influyen en el estado de salud y contacto con servicios de salud', [
            ['Z70','Consejo sexual'],['Z71','Contacto servicios salud'],
            ['Z72','Estilo de vida',[['Z72.0','Tabaco'],['Z72.1','Alcohol'],['Z72.2','Drogas'],['Z72.3','Falta ejercicio'],['Z72.4','Dieta inadecuada'],['Z72.5','Conducta sexual riesgo'],['Z72.8','Otros estilo vida'],['Z72.9','Estilo vida no especificado']]],
            ['Z73','Estrés'],['Z74','Dependencia cuidador'],['Z75','Servicios salud'],['Z76','Otros contactos'],
        ]);

        $this->addCodes($codes, 'XXI', 'Factores que influyen en el estado de salud y contacto con servicios de salud', [
            ['Z80','Neoplasia familiar'],['Z81','Trastorno mental familiar'],['Z82','Discapacidad familiar'],
            ['Z83','Otras familiares'],['Z84','Otras especificadas'],
            ['Z85','Neoplasia personal'],['Z86','Otras enfermedades personales'],
            ['Z87','Otras afecciones personales'],['Z88','Alergia'],['Z89','Ausencia adquirida'],
            ['Z90','Ausencia órgano'],['Z91','Factores riesgo personal'],['Z92','Antecedentes médicos'],
            ['Z93','Ostomía'],['Z94','Trasplante'],['Z95','Implantes cardíacos'],
            ['Z96','Implantes otros'],['Z97','Dispositivos'],['Z98','Estados postquirúrgicos'],
            ['Z99','Dependencia máquinas'],
        ]);

        // CAPÍTULO XXII: Códigos para propósitos especiales
        $this->addCodes($codes, 'XXII', 'Códigos para propósitos especiales', [
            ['U04','SARS',[['U04.9','SARS no especificado']]],
            ['U07','Emergencias COVID-19',[['U07.0','Intubación'],['U07.1','COVID-19 virus identificado'],['U07.2','COVID-19 probable'],['U07.3','Síndrome post-COVID-19']]],
            ['U08','Historia personal de COVID-19'],['U09','Condición post COVID-19 no especificada'],
        ]);

        $this->addCodes($codes, 'XXII', 'Códigos para propósitos especiales', [
            ['U80','Resistencia a antibióticos penicilina'],['U81','Resistencia a antibióticos meticilina'],
            ['U82','Resistencia a antibióticos vancomicina'],['U83','Resistencia a antibióticos otros'],
            ['U84','Resistencia a otros antimicrobianos'],['U85','Resistencia a antiparasitarios'],
            ['U86','Resistencia a antivirales'],['U88','Resistencia a múltiples antibióticos'],
            ['U89','Resistencia a otros antibióticos especificados'],
        ]);

        DB::table('cie10_codes')->insert($codes);
    }

    private function addCodes(&$codes, $capitulo, $capDesc, $items)
    {
        foreach ($items as $item) {
            $codigo = $item[0];
            $descripcion = $item[1];

            if (isset($item[2]) && is_array($item[2])) {
                $categoria = $codigo;
                $codes[] = [
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'categoria' => $categoria,
                    'categoria_descripcion' => $descripcion,
                    'capitulo' => $capitulo,
                    'capitulo_descripcion' => $capDesc,
                ];
                foreach ($item[2] as $sub) {
                    $codes[] = [
                        'codigo' => $sub[0],
                        'descripcion' => $sub[1],
                        'categoria' => $categoria,
                        'categoria_descripcion' => $descripcion,
                        'capitulo' => $capitulo,
                        'capitulo_descripcion' => $capDesc,
                    ];
                }
            } else {
                $codes[] = [
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'categoria' => $codigo,
                    'categoria_descripcion' => $descripcion,
                    'capitulo' => $capitulo,
                    'capitulo_descripcion' => $capDesc,
                ];
            }
        }
    }
}

