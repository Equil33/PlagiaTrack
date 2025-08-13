import smtplib
from email.mime.text import MIMEText

# --- CONFIGURATION ---
# Remplacez ces valeurs par vos propres informations
expediteur = "plagiatrack@gmail.com"
mot_de_passe ="pfuj genc axll hrjn"  # Utilisez un mot de passe d'application
destinataire = input(str("Saisissez votre Mail : "))
sujet = "Recopération de votre mot de passe"
corps_du_mail = """Bonjour,à vois
Vous souhaitez recupérer votre mot de passe , voici un mot de passe temporaire

"""

# --- CRÉATION DU MESSAGE ---
msg = MIMEText(corps_du_mail)
msg['Subject'] = sujet
msg['From'] = expediteur
msg['To'] = destinataire

# --- ENVOI DE L'EMAIL ---
try:
    # Connexion au serveur SMTP (ici, l'exemple de Gmail)
    server = smtplib.SMTP('smtp.gmail.com', 587)
    server.starttls()  # Démarrer le chiffrement TLS pour la sécurité
    
    # Connexion au compte
    server.login(expediteur, mot_de_passe)
    
    # Envoi du mail
    server.sendmail(expediteur, destinataire, msg.as_string())
    
    print("Email envoyé avec succès !")
    
except Exception as e:
    print(f"Une erreur est survenue : {e}")
finally:
    # Assurez-vous de toujours fermer la connexion au serveur
    server.quit()