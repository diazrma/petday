# Jingle de abertura do PetDay: "bloop" + arpejo de marimba (C6 E6 G6 C7) + brilho. 100% sintetizado.
import numpy as np, wave, sys
SR = 44100
def t(d): return np.arange(int(SR*d))/SR

def marimba(f, d=0.55, vol=1.0):
    x = t(d)
    # marimba: fundamental + parcial ~4x (e um pouco de ~10x) decaindo mais rápido
    s = (np.sin(2*np.pi*f*x)*np.exp(-x*7)
         + 0.35*np.sin(2*np.pi*f*3.93*x)*np.exp(-x*22)
         + 0.08*np.sin(2*np.pi*f*9.8*x)*np.exp(-x*45))
    att = np.minimum(1, x/0.004)        # ataque de 4 ms (sem clique)
    return vol*s*att

def bloop(d=0.12):
    x = t(d)
    f = 380 + 900*(x/d)**1.4            # glissando para cima, tipo bolha
    ph = 2*np.pi*np.cumsum(f)/SR
    return 0.55*np.sin(ph)*np.sin(np.pi*x/d)**2

def sparkle(f, d=0.6):
    x = t(d)
    return 0.18*np.sin(2*np.pi*f*x)*np.exp(-x*9)*np.minimum(1, x/0.002) * (1+0.3*np.sin(2*np.pi*11*x))

out = np.zeros(int(SR*1.25))
def put(sig, at):
    i = int(SR*at); n = min(len(sig), len(out)-i); out[i:i+n] += sig[:n]

put(bloop(), 0.0)
notes = [1046.50, 1318.51, 1567.98, 2093.00]   # C6 E6 G6 C7
for k, f in enumerate(notes):
    put(marimba(f, vol=[0.8, 0.8, 0.85, 1.0][k]), 0.11 + k*0.085)
put(sparkle(3135.96), 0.11 + 3*0.085 + 0.04)   # G7 brilhando
put(sparkle(4186.01), 0.11 + 3*0.085 + 0.10)   # C8

# "sala" leve: dois ecos curtos
rev = out.copy()
for dly, g in [(0.045, 0.22), (0.09, 0.12)]:
    i = int(SR*dly); rev[i:] += g*out[:-i]
out = rev
fade = int(SR*0.25); out[-fade:] *= np.linspace(1, 0, fade)**2
out = out/np.max(np.abs(out))*10**(-3/20)       # pico em -3 dBFS
with wave.open(sys.argv[1], 'wb') as w:
    w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes((out*32767).astype('<i2').tobytes())
print('ok', len(out)/SR, 's')
