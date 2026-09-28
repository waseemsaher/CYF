/**
 * Social Media Accounts Configuration
 * Replace the href values with your actual links when ready.
 */
export interface SocialLink {
  id: string;
  name: string;
  nameAr: string;
  url: string;
  ariaLabel: string;
}

export const socialLinks: SocialLink[] = [
  {
    id: 'facebook',
    name: 'Facebook',
    nameAr: 'فيسبوك',
    url: 'https://www.facebook.com/profile.php?id=61594746782523',
    ariaLabel: 'صفحة منصة Codeera على فيسبوك'
  },
  {
    id: 'youtube',
    name: 'YouTube',
    nameAr: 'يوتيوب',
    url: 'https://www.youtube.com/channel/UC6XPPHNpQJU2MpBhSPVarvA',
    ariaLabel: 'قناة منصة Codeera على يوتيوب'
  },
  {
    id: 'whatsapp',
    name: 'WhatsApp',
    nameAr: 'واتساب',
    url: 'https://wa.me/201555444726',
    ariaLabel: 'تواصل معنا عبر واتساب'
  },
  {
    id: 'telegram',
    name: 'Telegram',
    nameAr: 'تليجرام',
    url: 'https://t.me/code_eraa',
    ariaLabel: 'قناة منصة Codeera على تليجرام'
  }
];
