/**
 * Social Media Accounts Configuration
 * Consistent across the footer, trust section, and JSON-LD `sameAs`.
 */
export interface SocialLink {
  id: 'facebook' | 'youtube' | 'whatsapp';
  name: string;
  nameAr: string;
  url: string;
  ariaLabel: string;
}

export const WHATSAPP_SUPPORT_URL = 'https://wa.me/201555444726';

export const socialLinks: SocialLink[] = [
  {
    id: 'facebook',
    name: 'Facebook',
    nameAr: 'فيسبوك',
    url: 'https://www.facebook.com/profile.php?id=61594746782523',
    ariaLabel: 'صفحة Codeera - كوديرا على فيسبوك'
  },
  {
    id: 'youtube',
    name: 'YouTube',
    nameAr: 'يوتيوب',
    url: 'https://www.youtube.com/channel/UC6XPPHNpQJU2MpBhSPVarvA',
    ariaLabel: 'قناة Codeera - كوديرا على يوتيوب'
  },
  {
    id: 'whatsapp',
    name: 'WhatsApp',
    nameAr: 'واتساب',
    url: WHATSAPP_SUPPORT_URL,
    ariaLabel: 'تواصل مع دعم Codeera - كوديرا على واتساب'
  }
];

export const SAME_AS_URLS: string[] = socialLinks.map((s) => s.url);
